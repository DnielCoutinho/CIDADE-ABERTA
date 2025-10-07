/**
 * JavaScript do Painel Administrativo
 * Cidade Aberta Santarém
 */

// Configurações da API
const API_BASE = '../api/admin_users.php';

// Estado atual
let currentSection = 'dashboard';
let currentUser = null;
let users = [];
let logs = [];

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    initializeAdminPanel();
});

async function initializeAdminPanel() {
    try {
        console.log('🔧 Inicializando painel administrativo...');
        
        // Verificar se usuário está logado como admin
        await checkAdminSession();
        
        // Configurar event listeners
        setupEventListeners();
        
        // Forçar exibição do dashboard
        switchSection('dashboard');
        
        // Carregar dados iniciais
        console.log('📊 Carregando dados do dashboard...');
        await loadDashboardData();
        
        console.log('✅ Painel administrativo inicializado com sucesso');
        
    } catch (error) {
        console.error('❌ Erro ao inicializar painel:', error);
        console.error('Stack trace:', error.stack);
        
        // Mostrar erro específico em vez de mensagem genérica
        const errorMessage = error.message || 'Erro desconhecido';
        showAlert('error', `Erro detalhado: ${errorMessage}`);
        
        // NÃO redirecionar automaticamente - deixar usuário ver o erro
        console.log('⚠️ Redirecionamento automático desabilitado para debug');
        // setTimeout(() => {
        //     window.location.href = '../index.html';
        // }, 3000);
    }
}

/**
 * Verificar sessão administrativa
 */
async function checkAdminSession() {
    try {
        console.log('🔍 INICIANDO checkAdminSession()');
        
        const response = await fetch('../api/sessao.php');
        console.log('📡 Resposta fetch:', response.status, response.ok);
        
        const result = await response.json();
        console.log('📋 Dados da sessão recebidos:', result);
        
        console.log('🔍 Verificações:');
        console.log('  - result.success:', result.success);
        console.log('  - result.authenticated:', result.authenticated);
        console.log('  - result.user:', result.user);
        console.log('  - result.user.tipo:', result.user ? result.user.tipo : 'undefined');
        
        if (!result.success || !result.authenticated || result.user.tipo !== 'admin') {
            console.error('❌ Verificação falhou!');
            throw new Error('Sessão administrativa inválida');
        }
        
        console.log('✅ Verificação passou! Definindo currentUser...');
        currentUser = result.user;
        
        console.log('🔧 Chamando updateUserDisplay()...');
        updateUserDisplay();
        
        console.log('✅ checkAdminSession() concluído com sucesso');
        
    } catch (error) {
        console.error('💥 ERRO em checkAdminSession():', error);
        throw new Error('Acesso negado. Login administrativo necessário: ' + error.message);
    }
}

/**
 * Atualizar exibição do usuário atual
 */
function updateUserDisplay() {
    if (currentUser) {
        document.getElementById('current-admin-name').textContent = currentUser.nome;
        document.getElementById('current-admin-role').textContent = 
            currentUser.cargo || 'Administrador';
    }
}

/**
 * Configurar event listeners
 */
function setupEventListeners() {
    // Menu lateral
    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('click', function() {
            const section = this.dataset.section;
            switchSection(section);
        });
    });
    
    // Formulário de adicionar usuário
    document.getElementById('add-user-form').addEventListener('submit', handleAddUser);
    
    // Modais
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            hideModal(modal.id);
        });
    });
    
    // Clique fora do modal para fechar
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                hideModal(this.id);
            }
        });
    });
}

/**
 * Alternar seções
 */
function switchSection(section) {
    // Atualizar menu
    document.querySelectorAll('.menu-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`[data-section="${section}"]`).classList.add('active');
    
    // Atualizar conteúdo
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(`${section}-section`).classList.add('active');
    
    currentSection = section;
    
    // Carregar dados específicos da seção
    switch (section) {
        case 'dashboard':
            loadDashboardData();
            break;
        case 'users':
            loadUsers();
            break;
        case 'logs':
            loadLogs();
            break;
    }
}

/**
 * Carregar dados do dashboard
 */
async function loadDashboardData() {
    try {
        console.log('📊 INICIANDO loadDashboardData()');
        
        // Carregar estatísticas
        console.log('🔄 Fazendo requisição para estatísticas...');
        const statsResponse = await fetch(`${API_BASE}?action=stats`);
        
        console.log('📡 Resposta das estatísticas:', {
            status: statsResponse.status,
            ok: statsResponse.ok,
            url: statsResponse.url
        });
        
        const statsResult = await statsResponse.json();
        console.log('📊 Dados das estatísticas:', statsResult);
        
        if (statsResult.success) {
            const stats = statsResult.data;
            console.log('✅ Atualizando elementos do dashboard:', stats);
            
            document.getElementById('stat-admins-ativos').textContent = stats.admins_ativos || 0;
            document.getElementById('stat-admins-bloqueados').textContent = stats.admins_bloqueados || 0;
            document.getElementById('stat-logins-hoje').textContent = stats.logins_hoje || 0;
        } else {
            console.error('❌ Erro nas estatísticas:', statsResult.message);
        }
        
        // Carregar atividade recente
        console.log('🔄 Fazendo requisição para logs...');
        const logsResponse = await fetch(`${API_BASE}?action=logs&limit=5`);
        const logsResult = await logsResponse.json();
        console.log('📋 Dados dos logs:', logsResult);
        
        if (logsResult.success) {
            displayRecentActivity(logsResult.data);
        } else {
            console.error('❌ Erro nos logs:', logsResult.message);
        }
        
    } catch (error) {
        console.error('Erro ao carregar dashboard:', error);
        showAlert('error', 'Erro ao carregar dados do dashboard');
    }
}

/**
 * Exibir atividade recente
 */
function displayRecentActivity(recentLogs) {
    const container = document.getElementById('recent-activity');
    
    if (!recentLogs || recentLogs.length === 0) {
        container.innerHTML = '<p class="text-muted">Nenhuma atividade recente</p>';
        return;
    }
    
    const html = recentLogs.map(log => `
        <div class="activity-item">
            <div class="activity-icon">
                <i class="fas ${getLogIcon(log.acao)}"></i>
            </div>
            <div class="activity-content">
                <p><strong>${log.admin_nome}</strong> ${getLogDescription(log.acao)}</p>
                <small>${formatDateTime(log.data_acao)}</small>
            </div>
        </div>
    `).join('');
    
    container.innerHTML = html;
}

/**
 * Carregar usuários
 */
async function loadUsers() {
    try {
        showLoading('users-table-body');
        
        const response = await fetch(`${API_BASE}?action=list`);
        const result = await response.json();
        
        if (result.success) {
            users = result.data;
            displayUsers(users);
        } else {
            throw new Error(result.message);
        }
        
    } catch (error) {
        console.error('Erro ao carregar usuários:', error);
        showAlert('error', 'Erro ao carregar lista de usuários');
        document.getElementById('users-table-body').innerHTML = 
            '<tr><td colspan="8" class="text-center text-danger">Erro ao carregar usuários</td></tr>';
    }
}

/**
 * Exibir usuários na tabela
 */
function displayUsers(usersList) {
    const tbody = document.getElementById('users-table-body');
    
    if (!usersList || usersList.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">Nenhum usuário encontrado</td></tr>';
        return;
    }
    
    const html = usersList.map(user => `
        <tr>
            <td>
                <div class="user-info">
                    <strong>${escapeHtml(user.nome)}</strong>
                    ${user.criado_por_nome ? `<small>Criado por: ${escapeHtml(user.criado_por_nome)}</small>` : ''}
                </div>
            </td>
            <td>${escapeHtml(user.email)}</td>
            <td>${escapeHtml(user.cargo)}</td>
            <td>${escapeHtml(user.departamento)}</td>
            <td>
                <span class="level-badge level-${user.nivel_acesso}">
                    ${user.nivel_acesso === 'super_admin' ? 'Super Admin' : 'Admin'}
                </span>
            </td>
            <td>
                <span class="status-badge status-${user.ativo ? 'ativo' : 'inativo'}">
                    ${user.ativo ? 'Ativo' : 'Inativo'}
                </span>
                ${user.bloqueado ? '<span class="status-badge status-bloqueado">Bloqueado</span>' : ''}
                ${user.senha_temporaria ? '<span class="status-badge" style="background: #fff3cd; color: #856404;">Senha Temp.</span>' : ''}
            </td>
            <td>${user.data_ultimo_acesso ? formatDateTime(user.data_ultimo_acesso) : 'Nunca'}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-secondary" onclick="editUser(${user.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="resetPassword(${user.id})" title="Resetar Senha">
                        <i class="fas fa-key"></i>
                    </button>
                    <button class="btn btn-sm ${user.ativo ? 'btn-warning' : 'btn-success'}" 
                            onclick="toggleUserStatus(${user.id})" title="${user.ativo ? 'Desativar' : 'Ativar'}">
                        <i class="fas ${user.ativo ? 'fa-user-slash' : 'fa-user-check'}"></i>
                    </button>
                    ${user.id !== currentUser?.id ? `
                        <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})" title="Deletar">
                            <i class="fas fa-trash"></i>
                        </button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
    
    tbody.innerHTML = html;
}

/**
 * Carregar logs
 */
async function loadLogs() {
    try {
        showLoading('logs-list');
        
        const response = await fetch(`${API_BASE}?action=logs&limit=100`);
        const result = await response.json();
        
        if (result.success) {
            logs = result.data;
            displayLogs(logs);
        } else {
            throw new Error(result.message);
        }
        
    } catch (error) {
        console.error('Erro ao carregar logs:', error);
        showAlert('error', 'Erro ao carregar logs do sistema');
        document.getElementById('logs-list').innerHTML = 
            '<div class="text-center text-danger">Erro ao carregar logs</div>';
    }
}

/**
 * Exibir logs
 */
function displayLogs(logsList) {
    const container = document.getElementById('logs-list');
    
    if (!logsList || logsList.length === 0) {
        container.innerHTML = '<div class="text-center">Nenhum log encontrado</div>';
        return;
    }
    
    const html = logsList.map(log => `
        <div class="log-item">
            <div class="log-header">
                <span class="log-action">
                    <i class="fas ${getLogIcon(log.acao)}"></i>
                    ${getLogTitle(log.acao)}
                </span>
                <span class="log-time">${formatDateTime(log.data_acao)}</span>
            </div>
            <div class="log-details">
                <strong>${escapeHtml(log.admin_nome)}</strong> ${getLogDescription(log.acao)}
                ${log.detalhes ? `<br><small>Detalhes: ${escapeHtml(JSON.stringify(JSON.parse(log.detalhes), null, 2))}</small>` : ''}
            </div>
        </div>
    `).join('');
    
    container.innerHTML = html;
}

/**
 * Mostrar modal de adicionar usuário
 */
function showAddUserModal() {
    showModal('add-user-modal');
    document.getElementById('add-user-form').reset();
}

/**
 * Processar adição de usuário
 */
async function handleAddUser(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const userData = Object.fromEntries(formData.entries());
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    try {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...';
        submitBtn.disabled = true;
        
        const response = await fetch(API_BASE, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(userData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            hideModal('add-user-modal');
            showCredentialsModal(userData.email, result.data.senha_temporaria);
            loadUsers(); // Recarregar lista
            showAlert('success', 'Usuário criado com sucesso!');
        } else {
            throw new Error(result.message);
        }
        
    } catch (error) {
        console.error('Erro ao criar usuário:', error);
        showAlert('error', error.message || 'Erro ao criar usuário');
        
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

/**
 * Mostrar modal com credenciais
 */
function showCredentialsModal(email, password) {
    document.getElementById('cred-email').textContent = email;
    document.getElementById('cred-password').textContent = password;
    showModal('credentials-modal');
}

/**
 * Resetar senha do usuário
 */
async function resetPassword(userId) {
    if (!confirm('Tem certeza que deseja resetar a senha deste usuário?')) {
        return;
    }
    
    try {
        const response = await fetch(API_BASE, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: userId,
                action: 'reset_password'
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', `Senha resetada! Nova senha: ${result.nova_senha}`);
            loadUsers();
        } else {
            throw new Error(result.message);
        }
        
    } catch (error) {
        console.error('Erro ao resetar senha:', error);
        showAlert('error', error.message || 'Erro ao resetar senha');
    }
}

/**
 * Alternar status do usuário
 */
async function toggleUserStatus(userId) {
    try {
        const response = await fetch(API_BASE, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: userId,
                action: 'toggle_status'
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', 'Status alterado com sucesso!');
            loadUsers();
        } else {
            throw new Error(result.message);
        }
        
    } catch (error) {
        console.error('Erro ao alterar status:', error);
        showAlert('error', error.message || 'Erro ao alterar status');
    }
}

/**
 * Deletar usuário
 */
async function deleteUser(userId) {
    if (!confirm('Tem certeza que deseja DELETAR este usuário? Esta ação não pode ser desfeita!')) {
        return;
    }
    
    try {
        const response = await fetch(API_BASE, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: userId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', 'Usuário deletado com sucesso!');
            loadUsers();
        } else {
            throw new Error(result.message);
        }
        
    } catch (error) {
        console.error('Erro ao deletar usuário:', error);
        showAlert('error', error.message || 'Erro ao deletar usuário');
    }
}

/**
 * Voltar ao sistema principal
 */
function voltarSistema() {
    window.location.href = '../index.html';
}

/**
 * Mostrar modal
 */
function showModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

/**
 * Esconder modal
 */
function hideModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = 'auto';
}

/**
 * Mostrar loading
 */
function showLoading(elementId) {
    document.getElementById(elementId).innerHTML = '<div class="loading">Carregando...</div>';
}

/**
 * Mostrar alerta
 */
function showAlert(type, message) {
    // Criar elemento de alerta
    const alert = document.createElement('div');
    alert.className = `alert ${type}`;
    alert.innerHTML = `
        <i class="fas ${getAlertIcon(type)}"></i>
        <span>${escapeHtml(message)}</span>
    `;
    
    // Adicionar ao body
    document.body.appendChild(alert);
    
    // Posicionar
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '3000';
    alert.style.maxWidth = '400px';
    alert.style.borderRadius = '8px';
    alert.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    
    // Remover após 5 segundos
    setTimeout(() => {
        if (alert.parentNode) {
            alert.parentNode.removeChild(alert);
        }
    }, 5000);
}

/**
 * Copiar para clipboard
 */
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element.textContent;
    
    navigator.clipboard.writeText(text).then(() => {
        showAlert('success', 'Copiado para a área de transferência!');
    }).catch(() => {
        showAlert('error', 'Erro ao copiar');
    });
}

// Funções auxiliares
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR');
}

function getLogIcon(action) {
    const icons = {
        'usuario_criado': 'fa-user-plus',
        'usuario_atualizado': 'fa-user-edit',
        'usuario_deletado': 'fa-user-minus',
        'senha_resetada': 'fa-key',
        'status_alterado': 'fa-toggle-on',
        'admin_login': 'fa-sign-in-alt',
        'cidadao_login': 'fa-sign-in-alt',
        'sistema_inicializado': 'fa-power-off'
    };
    return icons[action] || 'fa-info-circle';
}

function getLogTitle(action) {
    const titles = {
        'usuario_criado': 'Usuário Criado',
        'usuario_atualizado': 'Usuário Atualizado',
        'usuario_deletado': 'Usuário Deletado',
        'senha_resetada': 'Senha Resetada',
        'status_alterado': 'Status Alterado',
        'admin_login': 'Login Admin',
        'cidadao_login': 'Login Cidadão',
        'sistema_inicializado': 'Sistema Inicializado'
    };
    return titles[action] || 'Ação Desconhecida';
}

function getLogDescription(action) {
    const descriptions = {
        'usuario_criado': 'criou um novo usuário',
        'usuario_atualizado': 'atualizou dados de usuário',
        'usuario_deletado': 'deletou um usuário',
        'senha_resetada': 'resetou senha de usuário',
        'status_alterado': 'alterou status de usuário',
        'admin_login': 'fez login como administrador',
        'cidadao_login': 'fez login como cidadão',
        'sistema_inicializado': 'inicializou o sistema'
    };
    return descriptions[action] || 'executou uma ação';
}

function getAlertIcon(type) {
    const icons = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-triangle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    };
    return icons[type] || 'fa-info-circle';
}