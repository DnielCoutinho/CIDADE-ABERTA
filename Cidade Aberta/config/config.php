<?php
/**
 * Arquivo de configuração central do sistema Cidade Aberta
 * Centraliza todas as configurações em um local
 */

// Configurações do ambiente
define('ENVIRONMENT', 'development'); // development, production
define('DEBUG_MODE', true);

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'cidade_aberta');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurações da aplicação
define('APP_NAME', 'Cidade Aberta');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/Cidade%20Aberta');

// Configurações de segurança
define('SESSION_TIMEOUT', 3600); // 1 hora
define('MAX_LOGIN_ATTEMPTS', 5);
define('CSRF_TOKEN_EXPIRE', 1800); // 30 minutos

// Configurações de upload
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Configurações de logs
define('LOG_PATH', __DIR__ . '/../logs/');
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// Configurações de email (para futuras notificações)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('FROM_EMAIL', 'noreply@cidadeaberta.santarem.pa.gov.br');
define('FROM_NAME', 'Portal Cidade Aberta');

// Configurações do mapa
define('MAP_DEFAULT_LAT', -2.4194);
define('MAP_DEFAULT_LNG', -54.7083);
define('MAP_DEFAULT_ZOOM', 13);
define('MAP_MAX_ZOOM', 18);
define('MAP_MIN_ZOOM', 10);

// Configurações de API
define('API_RATE_LIMIT', 100); // requests por hora por IP
define('API_VERSION', 'v1');

// Status das ocorrências
define('STATUS_PENDENTE', 'pendente');
define('STATUS_EM_ANALISE', 'em_analise');
define('STATUS_EM_ANDAMENTO', 'em_andamento');
define('STATUS_CONCLUIDA', 'concluida');
define('STATUS_CANCELADA', 'cancelada');

// Tipos de ocorrências
$TIPOS_OCORRENCIA = [
    'buraco' => 'Buraco na Via',
    'iluminacao' => 'Iluminação Pública',
    'lixo' => 'Limpeza Urbana',
    'agua' => 'Abastecimento de Água',
    'esgoto' => 'Esgoto e Saneamento',
    'calcada' => 'Calçada e Acessibilidade',
    'sinalizacao' => 'Sinalização de Trânsito',
    'outro' => 'Outros'
];

// Função para obter configuração
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

// Função para verificar se está em modo debug
function isDebugMode() {
    return getConfig('DEBUG_MODE', false);
}

// Função para obter URL completa
function getFullUrl($path = '') {
    return rtrim(getConfig('APP_URL'), '/') . '/' . ltrim($path, '/');
}

// Função para log
function writeLog($level, $message, $context = []) {
    if (!in_array($level, ['DEBUG', 'INFO', 'WARNING', 'ERROR'])) {
        return false;
    }
    
    $logFile = LOG_PATH . 'app_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    $logLine = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;
    
    return file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}

// Headers de segurança padrão
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    if (!isDebugMode()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Verificar se diretórios necessários existem
function checkDirectories() {
    $dirs = [
        LOG_PATH,
        UPLOAD_PATH
    ];
    
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
            writeLog('INFO', "Diretório criado: {$dir}");
        }
    }
}

// Inicialização automática
checkDirectories();
setSecurityHeaders();

// Log de inicialização
if (isDebugMode()) {
    writeLog('DEBUG', 'Sistema inicializado', [
        'version' => APP_VERSION,
        'environment' => ENVIRONMENT,
        'timestamp' => time()
    ]);
}
?>