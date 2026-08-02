/**
 * CARTA DIGITAL - INSTALADOR v2.0
 * JavaScript interactivo
 */

// ========== STATE MANAGEMENT ==========
const installerState = {
    currentStep: 1,
    requirements: {},
    dbConfig: {
        db_host: 'localhost',
        db_user: 'root',
        db_pass: '',
        db_name: 'carta_digital',
        db_port: 3306
    },
    installationTasks: [],
    errors: []
};

// ========== INITIALIZATION ==========
document.addEventListener('DOMContentLoaded', () => {
    initializeInstaller();
});

function initializeInstaller() {
    // Load form values from localStorage if exist
    const savedConfig = localStorage.getItem('cartaInstallerConfig');
    if (savedConfig) {
        try {
            installerState.dbConfig = JSON.parse(savedConfig);
            updateFormValues();
        } catch (e) {
            console.log('No saved config found');
        }
    }

    // Event listeners
    attachEventListeners();
    
    // Start requirement check
    checkRequirements();
}

function attachEventListeners() {
    // Stepper clicks
    document.querySelectorAll('.step').forEach(step => {
        step.addEventListener('click', function() {
            const stepNum = this.dataset.step;
            if (parseInt(stepNum) <= installerState.currentStep) {
                goToStep(parseInt(stepNum));
            }
        });
    });

    // Config form
    const configForm = document.getElementById('config-form');
    if (configForm) {
        configForm.addEventListener('change', () => {
            saveFormValues();
        });
        
        document.getElementById('btn-test-db').addEventListener('click', testDatabaseConnection);
    }

    // Navigation buttons
    document.getElementById('btn-recheck').addEventListener('click', checkRequirements);
    document.getElementById('btn-retry-install').addEventListener('click', () => {
        installerState.errors = [];
        startInstallation();
    });
}

// ========== STEP NAVIGATION ==========
function goToStep(step) {
    // Hide all content
    document.querySelectorAll('.step-content').forEach(el => {
        el.classList.remove('active');
    });

    // Show target content
    const targetContent = document.getElementById(`step-${step}`);
    if (targetContent) {
        targetContent.classList.add('active');
    }

    // Update stepper
    updateStepper(step);
    installerState.currentStep = step;

    // Scroll to top
    document.querySelector('.installer-content').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function updateStepper(currentStep) {
    document.querySelectorAll('.step').forEach((step, index) => {
        const stepNum = index + 1;
        
        step.classList.remove('active', 'completed');
        
        if (stepNum === currentStep) {
            step.classList.add('active');
        } else if (stepNum < currentStep) {
            step.classList.add('completed');
        }
    });
}

// ========== REQUIREMENT CHECKING ==========
function checkRequirements() {
    const requirementsList = document.getElementById('requirements-list');
    requirementsList.innerHTML = '<div class="loading"><div class="spinner"></div><p>Verificando requisitos...</p></div>';

    fetch('api/check.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        installerState.requirements = data;
        displayRequirements(data);
        checkIfCanProceed();
    })
    .catch(error => {
        console.error('Error:', error);
        requirementsList.innerHTML = `
            <div class="alert alert-danger">
                <strong>Error al verificar requisitos:</strong> ${error.message}
            </div>
        `;
        document.getElementById('btn-recheck').style.display = 'inline-block';
    });
}

function displayRequirements(requirements) {
    const requirementsList = document.getElementById('requirements-list');
    requirementsList.innerHTML = '';

    const categories = {
        php: { title: '🔧 PHP', icon: '⚙️' },
        extensions: { title: '📦 Extensiones PHP', icon: '📦' },
        mysql: { title: '🗄️ Base de Datos', icon: '🗄️' },
        permissions: { title: '📂 Permisos', icon: '📂' },
        webserver: { title: '🌐 Servidor Web', icon: '🌐' }
    };

    Object.entries(categories).forEach(([category, categoryInfo]) => {
        if (requirements[category]) {
            const items = requirements[category];
            items.forEach((item, idx) => {
                const itemEl = createRequirementItem(item, categoryInfo.icon);
                requirementsList.appendChild(itemEl);
            });
        }
    });

    document.getElementById('btn-recheck').style.display = 'inline-block';
}

function createRequirementItem(item, icon) {
    const div = document.createElement('div');
    div.className = 'requirement-item';
    
    const statusClass = item.status === 'success' ? 'success' : (item.status === 'warning' ? 'warning' : 'error');
    const statusIcon = item.status === 'success' ? '✅' : (item.status === 'warning' ? '⚠️' : '❌');

    div.innerHTML = `
        <div class="requirement-left">
            <div class="requirement-icon">${icon}</div>
            <div class="requirement-info">
                <h4>${item.name}</h4>
                <small>${item.message}</small>
            </div>
        </div>
        <div class="requirement-status ${statusClass}">
            ${statusIcon} ${item.status.charAt(0).toUpperCase() + item.status.slice(1)}
        </div>
    `;

    return div;
}

function checkIfCanProceed() {
    const hasErrors = Object.values(installerState.requirements).flat().some(r => r.status === 'error');
    
    if (!hasErrors) {
        document.getElementById('btn-next-step1').style.display = 'inline-block';
    } else {
        document.getElementById('btn-next-step1').style.display = 'none';
    }
}

// ========== DATABASE TESTING ==========
function saveFormValues() {
    const form = document.getElementById('config-form');
    const formData = new FormData(form);
    
    for (let [key, value] of formData.entries()) {
        installerState.dbConfig[key] = value;
    }

    localStorage.setItem('cartaInstallerConfig', JSON.stringify(installerState.dbConfig));
}

function updateFormValues() {
    const form = document.getElementById('config-form');
    if (!form) return;

    Object.entries(installerState.dbConfig).forEach(([key, value]) => {
        const input = form.elements[key];
        if (input) {
            input.value = value;
        }
    });
}

function testDatabaseConnection() {
    saveFormValues();

    const btn = document.getElementById('btn-test-db');
    const resultDiv = document.getElementById('db-test-result');
    
    btn.disabled = true;
    btn.textContent = '⏳ Probando...';
    resultDiv.style.display = 'none';

    fetch('api/check.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'test_db',
            config: installerState.dbConfig
        })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.textContent = '🧪 Probar Conexión';
        resultDiv.style.display = 'block';

        if (data.success) {
            resultDiv.className = 'alert alert-success';
            resultDiv.innerHTML = `
                <strong>✅ Conexión exitosa</strong><br>
                Base de datos: <code>${data.database}</code><br>
                Versión MySQL: ${data.mysql_version}
            `;
            document.getElementById('btn-next-step2').style.display = 'inline-block';
        } else {
            resultDiv.className = 'alert alert-danger';
            resultDiv.innerHTML = `
                <strong>❌ Error de conexión</strong><br>
                ${data.error}
            `;
            document.getElementById('btn-next-step2').style.display = 'none';
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.textContent = '🧪 Probar Conexión';
        resultDiv.className = 'alert alert-danger';
        resultDiv.innerHTML = `<strong>Error:</strong> ${error.message}`;
        resultDiv.style.display = 'block';
        document.getElementById('btn-next-step2').style.display = 'none';
    });
}

// ========== BUTTON EVENT HANDLERS ==========
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('btn-next-step1').addEventListener('click', () => {
        goToStep(2);
    });

    document.getElementById('btn-next-step2').addEventListener('click', () => {
        goToStep(3);
        startInstallation();
    });
});

// ========== INSTALLATION ==========
function startInstallation() {
    saveFormValues();
    goToStep(3);

    const tasks = [
        { id: 'create_db', name: 'Crear base de datos' },
        { id: 'import_schema', name: 'Importar esquema' },
        { id: 'install_composer', name: 'Instalar dependencias' },
        { id: 'check_permissions', name: 'Configurar directorios' },
        { id: 'create_config', name: 'Crear configuración' }
    ];

    runInstallationTasks(tasks, 0);
}

function runInstallationTasks(tasks, index) {
    if (index >= tasks.length) {
        completeInstallation();
        return;
    }

    const task = tasks[index];
    const taskEl = document.querySelector(`[data-task="${task.id}"]`);
    
    updateTaskStatus(taskEl, 'in-progress');

    fetch('api/install.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: task.id,
            config: installerState.dbConfig
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTaskStatus(taskEl, 'success', data.message);
            // Continue to next task
            setTimeout(() => {
                runInstallationTasks(tasks, index + 1);
            }, 500);
        } else {
            updateTaskStatus(taskEl, 'error', data.error);
            installerState.errors.push(`${task.name}: ${data.error}`);
            
            // Try to continue with next task
            setTimeout(() => {
                runInstallationTasks(tasks, index + 1);
            }, 500);
        }
    })
    .catch(error => {
        updateTaskStatus(taskEl, 'error', error.message);
        installerState.errors.push(`${task.name}: ${error.message}`);
        
        setTimeout(() => {
            runInstallationTasks(tasks, index + 1);
        }, 500);
    });
}

function updateTaskStatus(taskEl, status, message = '') {
    if (!taskEl) return;

    taskEl.classList.add(status);

    const statusIcon = status === 'success' ? '✅' : (status === 'error' ? '❌' : '⏳');
    const progressIcon = taskEl.querySelector('.progress-icon');
    
    if (progressIcon) {
        progressIcon.textContent = statusIcon;
    }

    const progressFill = taskEl.querySelector('.progress-fill');
    if (progressFill && status !== 'in-progress') {
        progressFill.style.width = '100%';
    }
}

function completeInstallation() {
    // Hide retry button if no errors
    if (installerState.errors.length === 0) {
        document.getElementById('btn-retry-install').style.display = 'none';
        goToStep(4);
    } else {
        // Show errors
        const errorContainer = document.getElementById('installation-errors');
        const errorList = document.getElementById('error-list');
        
        errorContainer.style.display = 'block';
        errorList.innerHTML = installerState.errors
            .map(error => `<li>${error}</li>`)
            .join('');
        
        document.getElementById('btn-retry-install').style.display = 'inline-block';
    }
}

// ========== UTILITY FUNCTIONS ==========
function formatSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}
