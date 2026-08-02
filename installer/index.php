<?php
// Verificar instalación previa
include_once 'check-installation.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador - Carta Digital</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="installer-wrapper">
        <!-- Header -->
        <div class="installer-header">
            <div class="container">
                <div class="header-content">
                    <h1>🍕 Carta Digital</h1>
                    <p>Instalador de Sistema</p>
                </div>
                <div class="header-version">
                    <span class="badge">v2.0</span>
                </div>
            </div>
        </div>

        <!-- Main Container -->
        <div class="installer-container">
            <div class="container">
                <!-- Stepper -->
                <div class="stepper">
                    <div class="step active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-label">Verificación</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-label">Configuración</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step" data-step="3">
                        <div class="step-number">3</div>
                        <div class="step-label">Instalación</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step" data-step="4">
                        <div class="step-number">4</div>
                        <div class="step-label">Finalizado</div>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="installer-content">
                    <!-- PASO 1: VERIFICACIÓN -->
                    <div class="step-content active" id="step-1">
                        <h2>Verificación de Requisitos</h2>
                        <p class="text-muted">Estamos validando que tu servidor cumpla con los requisitos mínimos.</p>
                        
                        <div id="requirements-list" class="requirements-list">
                            <div class="loading">
                                <div class="spinner"></div>
                                <p>Verificando requisitos...</p>
                            </div>
                        </div>

                        <div class="actions">
                            <button type="button" class="btn btn-primary" id="btn-recheck" style="display:none;">
                                🔄 Verificar de Nuevo
                            </button>
                            <button type="button" class="btn btn-success" id="btn-next-step1" style="display:none;" data-target="2">
                                Continuar →
                            </button>
                        </div>
                    </div>

                    <!-- PASO 2: CONFIGURACIÓN -->
                    <div class="step-content" id="step-2">
                        <h2>Configuración de Base de Datos</h2>
                        <p class="text-muted">Configura los parámetros de conexión a tu base de datos MySQL.</p>

                        <form id="config-form" class="config-form">
                            <div class="form-group">
                                <label>Servidor MySQL</label>
                                <input type="text" name="db_host" value="localhost" required class="form-control">
                                <small>Usualmente: localhost o 127.0.0.1</small>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Usuario MySQL</label>
                                    <input type="text" name="db_user" value="root" required class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Contraseña</label>
                                    <input type="password" name="db_pass" class="form-control" placeholder="Dejar vacío si no la hay">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Base de Datos</label>
                                <input type="text" name="db_name" value="carta_digital" required class="form-control">
                                <small>Se creará automáticamente si no existe</small>
                            </div>

                            <div class="form-group">
                                <label>Port (Puerto)</label>
                                <input type="number" name="db_port" value="3306" required class="form-control">
                                <small>Puerto por defecto de MySQL: 3306</small>
                            </div>

                            <div id="db-test-result" class="alert" style="display:none;"></div>

                            <div class="actions">
                                <button type="button" class="btn btn-secondary" onclick="goToStep(1)">
                                    ← Atrás
                                </button>
                                <button type="button" class="btn btn-info" id="btn-test-db">
                                    🧪 Probar Conexión
                                </button>
                                <button type="button" class="btn btn-success" id="btn-next-step2" style="display:none;" data-target="3">
                                    Continuar →
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- PASO 3: INSTALACIÓN -->
                    <div class="step-content" id="step-3">
                        <h2>Instalación en Progreso</h2>
                        <p class="text-muted">Por favor, no cierres esta ventana. Este proceso puede tomar unos minutos.</p>

                        <div id="installation-progress" class="installation-progress">
                            <div class="progress-item" data-task="create_db">
                                <div class="progress-label">
                                    <span class="progress-icon">⏳</span>
                                    <span class="progress-text">Creando base de datos...</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill"></div>
                                </div>
                            </div>

                            <div class="progress-item" data-task="import_schema">
                                <div class="progress-label">
                                    <span class="progress-icon">⏳</span>
                                    <span class="progress-text">Importando esquema...</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill"></div>
                                </div>
                            </div>

                            <div class="progress-item" data-task="install_composer">
                                <div class="progress-label">
                                    <span class="progress-icon">⏳</span>
                                    <span class="progress-text">Instalando dependencias...</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill"></div>
                                </div>
                            </div>

                            <div class="progress-item" data-task="check_permissions">
                                <div class="progress-label">
                                    <span class="progress-icon">⏳</span>
                                    <span class="progress-text">Creando directorios...</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill"></div>
                                </div>
                            </div>

                            <div class="progress-item" data-task="create_config">
                                <div class="progress-label">
                                    <span class="progress-icon">⏳</span>
                                    <span class="progress-text">Configurando sistema...</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill"></div>
                                </div>
                            </div>
                        </div>

                        <div id="installation-errors" class="alert alert-danger" style="display:none; margin-top:20px;">
                            <h4>⚠️ Errores durante la instalación:</h4>
                            <ul id="error-list"></ul>
                        </div>

                        <div class="actions">
                            <button type="button" class="btn btn-secondary" id="btn-retry-install" style="display:none;">
                                🔄 Reintentar
                            </button>
                        </div>
                    </div>

                    <!-- PASO 4: FINALIZADO -->
                    <div class="step-content" id="step-4">
                        <div class="success-container">
                            <div class="success-icon">✅</div>
                            <h2>¡Instalación Completada!</h2>
                            <p class="text-muted">Tu sistema Carta Digital está listo para usar.</p>

                            <div class="success-info">
                                <div class="info-box">
                                    <h4>📊 Datos de Administrador</h4>
                                    <div class="info-item">
                                        <label>Usuario:</label>
                                        <code>admin</code>
                                    </div>
                                    <div class="info-item">
                                        <label>Contraseña:</label>
                                        <code>admin123</code>
                                    </div>
                                    <small class="warning-text">⚠️ Cámbia la contraseña inmediatamente después de iniciar sesión</small>
                                </div>

                                <div class="info-box">
                                    <h4>🔗 Enlaces Rápidos</h4>
                                    <div class="links">
                                        <a href="../index.php" class="btn btn-info" target="_blank">
                                            👀 Ver Carta Digital
                                        </a>
                                        <a href="../admin/" class="btn btn-primary" target="_blank">
                                            ⚙️ Panel de Administración
                                        </a>
                                    </div>
                                </div>

                                <div class="info-box">
                                    <h4>📚 Próximos Pasos</h4>
                                    <ol class="next-steps">
                                        <li>Inicia sesión en el panel admin</li>
                                        <li>Cambiar contraseña de administrador</li>
                                        <li>Configura datos de tu negocio (nombre, logo, colores)</li>
                                        <li>Integra Culqi para pagos con tarjeta</li>
                                        <li>Crea tus productos y categorías</li>
                                        <li>Prueba el sistema completamente</li>
                                    </ol>
                                </div>
                            </div>

                            <div class="actions">
                                <button type="button" class="btn btn-secondary" onclick="location.reload()">
                                    🔄 Ejecutar Instalador de Nuevo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="installer-footer">
            <p>© 2024 Carta Digital - Instalador v2.0 | Soporte: <a href="https://github.com" target="_blank">Documentación</a></p>
        </div>
    </div>

    <script src="assets/script.js"></script>
</body>
</html>
