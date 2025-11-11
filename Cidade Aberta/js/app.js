// Configurações globais
const CONFIG = {
    API_BASE_URL: './api',
    API_ENDPOINTS: {
        ocorrencias: './api/ocorrencias_simple.php',
        login: './api/login_unificado.php',
        loginCidadao: './api/login_cidadao.php',
        sessao: './api/sessao.php',
        rastreamento: './api/rastreamento_simple.php',
        contato: './api/contato_simple.php',
        estatisticas: './api/stats.php'
    },
    MAP: {
        defaultCenter: [-2.419444, -54.708333], // Centro de Santarém, PA (Av. Tapajós)
        defaultZoom: 14,
        maxZoom: 18,
        minZoom: 12
    }
};

// Estado global da aplicação
const state = {
    map: null,
    markersLayer: null,
    markers: [],
    ocorrencias: [],
    selectedLocation: null,
    tempMarker: null,
    tempTrackingMarker: null,
    currentUser: null,
    isAuthenticated: false,
    userType: null // 'admin', 'cidadao', ou null
};

// Funções para gerenciar modais (definidas cedo para uso global)
function showModal(modalId) {
    console.log('📱 Tentando mostrar modal:', modalId);
    const modal = document.getElementById(modalId);
    if (modal) {
        console.log('✅ Modal encontrado, exibindo...');
        modal.style.display = 'block';
        
        // Salva o overflow original antes de modificar
        if (!document.body.dataset.originalOverflow) {
            document.body.dataset.originalOverflow = document.body.style.overflow || 'auto';
        }
        document.body.style.overflow = 'hidden';
        
        // Força o scroll do modal para o topo
        setTimeout(() => {
            modal.scrollTop = 0;
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.scrollTop = 0;
            }
        }, 10);
        
    } else {
        console.error('❌ Modal não encontrado:', modalId);
    }
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        
        // Para modais dinâmicos (como admin-options-modal), remover do DOM
        if (modalId === 'admin-options-modal') {
            modal.remove();
        }
        
        // Restaura o overflow original ou define como auto
        const originalOverflow = document.body.dataset.originalOverflow || 'auto';
        document.body.style.overflow = originalOverflow;
        // Remove o atributo para limpeza
        delete document.body.dataset.originalOverflow;
    }
}

// Função de Login Unificado
function showLoginModal() {
    console.log('🔑 Abrindo modal de login unificado...');
    showModal('login-modal');
}

// Funções para modais de segurança
function showRecuperarSenhaModal() {
    hideModal('cidadao-modal');
    showModal('recuperar-senha-modal');
}

function showCadastroModal() {
    hideModal('cidadao-modal');
    showModal('cadastro-modal');
}

// Função para alternar visibilidade da senha
function togglePasswordVisibility(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Função para verificar força da senha
function checkPasswordStrength(password) {
    const strengthElement = document.getElementById('password-strength');
    if (!strengthElement) return;
    
    let score = 0;
    
    // Critérios de força
    if (password.length >= 8) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    
    // Aplicar classe baseada na força
    strengthElement.className = 'password-strength';
    if (score < 3) {
        strengthElement.classList.add('weak');
    } else if (score < 5) {
        strengthElement.classList.add('medium');
    } else {
        strengthElement.classList.add('strong');
    }
}

// Função para validar email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Função para criptografar senha (básico)
function hashPassword(password) {
    // Em produção, usar uma biblioteca de hash segura
    let hash = 0;
    for (let i = 0; i < password.length; i++) {
        const char = password.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash; // Converte para 32bit
    }
    return Math.abs(hash).toString(16);
}

// Função para limitar tentativas de login
let loginAttempts = {};
const MAX_ATTEMPTS = 5;
const LOCKOUT_TIME = 15 * 60 * 1000; // 15 minutos

function checkLoginAttempts(email) {
    const now = Date.now();
    
    if (!loginAttempts[email]) {
        loginAttempts[email] = { count: 0, lastAttempt: now };
        return true;
    }
    
    const attempts = loginAttempts[email];
    
    // Resetar se passou o tempo de bloqueio
    if (now - attempts.lastAttempt > LOCKOUT_TIME) {
        attempts.count = 0;
        attempts.lastAttempt = now;
        return true;
    }
    
    // Verificar se excedeu tentativas
    if (attempts.count >= MAX_ATTEMPTS) {
        const timeLeft = Math.ceil((LOCKOUT_TIME - (now - attempts.lastAttempt)) / 60000);
        showMessage('error', `Muitas tentativas. Tente novamente em ${timeLeft} minutos.`);
        return false;
    }
    
    return true;
}

function recordFailedAttempt(email) {
    const now = Date.now();
    if (!loginAttempts[email]) {
        loginAttempts[email] = { count: 0, lastAttempt: now };
    }
    
    loginAttempts[email].count++;
    loginAttempts[email].lastAttempt = now;
}

function clearLoginAttempts(email) {
    if (loginAttempts[email]) {
        loginAttempts[email].count = 0;
    }
}

// Exposição global das funções
window.showModal = showModal;
window.hideModal = hideModal;
window.showLoginModal = showLoginModal;
window.showRecuperarSenhaModal = showRecuperarSenhaModal;
window.showCadastroModal = showCadastroModal;
window.togglePasswordVisibility = togglePasswordVisibility;

// Inicialização da aplicação
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Função para garantir que o scroll da página esteja funcionando
function ensurePageScroll() {
    // Remove qualquer overflow hidden que possa estar bloqueando o scroll
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
    
    // Força a definição de scroll automático
    document.body.style.overflow = 'auto';
    document.documentElement.style.overflow = 'auto';
    
    // Remove qualquer atributo de dataset que possa estar causando problemas
    delete document.body.dataset.originalOverflow;
    
    console.log('📜 Scroll da página verificado e habilitado');
}

// Função de debug para verificar o status do scroll
function debugScrollStatus() {
    console.log('🔍 Status do Scroll:');
    console.log('- Body overflow:', document.body.style.overflow || 'não definido');
    console.log('- HTML overflow:', document.documentElement.style.overflow || 'não definido');
    console.log('- Body height:', document.body.scrollHeight + 'px');
    console.log('- Window height:', window.innerHeight + 'px');
    console.log('- Scroll possível:', document.body.scrollHeight > window.innerHeight ? 'SIM' : 'NÃO');
}

// Expor função de debug globalmente
window.debugScrollStatus = debugScrollStatus;

function initializeApp() {
    try {
        console.log('🚀 Inicializando aplicação...');
        
        // Garantir que o scroll da página esteja funcionando
        ensurePageScroll();
        
        // Verificar sessão do usuário primeiro
        checkUserSession();
        
        initializeMap();
        setupEventListeners();
        
        // Delay para garantir que o DOM está carregado
        setTimeout(() => {
            console.log('📊 Carregando estatísticas...');
            loadEstatisticas();
        }, 1000);
        
        console.log('✅ Aplicação inicializada com sucesso');
    } catch (error) {
        console.error('❌ Erro ao inicializar aplicação:', error);
    }
}

function setupEventListeners() {
    // Formulário de ocorrência
    const ocorrenciaForm = document.getElementById('form-ocorrencia');
    if (ocorrenciaForm) {
        ocorrenciaForm.addEventListener('submit', handleOcorrenciaSubmit);
    }

    // Formulário de rastreamento
    const rastreamentoForm = document.getElementById('form-rastreamento');
    if (rastreamentoForm) {
        rastreamentoForm.addEventListener('submit', handleRastreamentoSubmit);
    }

    // Formulário de contato
    const contatoForm = document.getElementById('form-contato');
    if (contatoForm) {
        contatoForm.addEventListener('submit', handleContatoSubmit);
    }

    // Formulário de login unificado
    const loginForm = document.getElementById('form-login');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLoginUnificadoSubmit);
    }

    // Formulário de recuperação de senha
    const recuperarSenhaForm = document.getElementById('form-recuperar-senha');
    if (recuperarSenhaForm) {
        recuperarSenhaForm.addEventListener('submit', handleRecuperarSenhaSubmit);
    }

    // Formulário de cadastro
    const cadastroForm = document.getElementById('form-cadastro-cidadao');
    if (cadastroForm) {
        cadastroForm.addEventListener('submit', handleCadastroSubmit);
    }

    // Verificação de força da senha em tempo real
    const cadastroSenhaInput = document.getElementById('cadastro-senha');
    if (cadastroSenhaInput) {
        cadastroSenhaInput.addEventListener('input', (e) => {
            checkPasswordStrength(e.target.value);
        });
    }

    // Validação de confirmação de senha
    const confirmarSenhaInput = document.getElementById('cadastro-confirmar-senha');
    if (confirmarSenhaInput && cadastroSenhaInput) {
        confirmarSenhaInput.addEventListener('input', () => {
            const senha = cadastroSenhaInput.value;
            const confirmar = confirmarSenhaInput.value;
            
            if (confirmar && senha !== confirmar) {
                confirmarSenhaInput.setCustomValidity('As senhas não coincidem');
            } else {
                confirmarSenhaInput.setCustomValidity('');
            }
        });
    }

    // Upload de arquivo
    const photoInput = document.getElementById('foto-ocorrencia');
    if (photoInput) {
        photoInput.addEventListener('change', handlePhotoUpload);
    }

    // Navegação suave
    setupSmoothScrolling();

    // Modal
    setupModalEvents();

    // Busca no mapa
    setupMapSearch();

    // Filtros do mapa
    setupMapFilters();

    // Auto-formatação do input de rastreamento
    setupTrackingInput();
    
    // Verificação periódica do scroll (a cada 5 segundos)
    setInterval(() => {
        // Verifica se o body tem overflow hidden sem que haja um modal aberto
        const openModals = document.querySelectorAll('.modal[style*="block"]');
        if (openModals.length === 0 && document.body.style.overflow === 'hidden') {
            console.log('⚠️ Scroll bloqueado sem modal aberto. Corrigindo...');
            ensurePageScroll();
        }
    }, 5000);
}

// Inicialização do mapa
function initializeMap() {
    try {
        const mapElement = document.getElementById('leaflet-map');
        if (!mapElement) {
            throw new Error('Elemento do mapa não encontrado');
        }

        // Criar o mapa
        state.map = L.map('leaflet-map', {
            center: CONFIG.MAP.defaultCenter,
            zoom: CONFIG.MAP.defaultZoom,
            maxZoom: CONFIG.MAP.maxZoom,
            minZoom: CONFIG.MAP.minZoom,
            zoomControl: true,
            attributionControl: true
        });

        // Adicionar camada de tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: CONFIG.MAP.maxZoom
        }).addTo(state.map);

        // Criar camada para marcadores
        state.markersLayer = L.layerGroup().addTo(state.map);

        // Configurar eventos do mapa
        setupMapEvents();

        // Carregar ocorrências reais da API
        loadOcorrencias();

        // Adicionar controle de localização
        addLocationControl();

        console.log('Mapa inicializado com sucesso');
        
    } catch (error) {
        console.error('Erro ao inicializar o mapa:', error);
        showMapError();
    }
}

function showMapError() {
    const mapElement = document.getElementById('leaflet-map');
    if (mapElement) {
        mapElement.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: center; height: 100%; flex-direction: column; color: var(--dark-gray);">
                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #dc3545; margin-bottom: 1rem;"></i>
                <p style="text-align: center;">Erro ao carregar o mapa.<br>Verifique sua conexão com a internet.</p>
            </div>
        `;
    }
}

// Função para buscar ocorrências da API
async function loadOcorrencias() {
    try {
        console.log('📍 Carregando ocorrências...', { userType: state.userType });
        
        const response = await fetch(CONFIG.API_ENDPOINTS.ocorrencias);
        const result = await response.json();
        
        if (result.success && result.data && result.data.ocorrencias) {
            // Limpar marcadores existentes
            state.markersLayer.clearLayers();
            state.markers = [];
            
            state.ocorrencias = result.data.ocorrencias.map(item => ({
                id: item.id,
                codigo: item.codigo,
                tipo: item.tipo,
                descricao: item.descricao,
                endereco: item.endereco,
                coordenadas: [parseFloat(item.latitude), parseFloat(item.longitude)],
                latitude: parseFloat(item.latitude),
                longitude: parseFloat(item.longitude),
                status: item.status,
                data_criacao: item.data_criacao,
                nome_cidadao: item.nome_cidadao,
                email_cidadao: item.email_cidadao
            }));
            
            // Adicionar marcadores no mapa
            console.log('🗺️ Adicionando marcadores no mapa...', state.ocorrencias.length);
            state.ocorrencias.forEach(ocorrencia => {
                console.log('📍 Processando ocorrência:', ocorrencia.codigo, ocorrencia.latitude, ocorrencia.longitude);
                if (ocorrencia.latitude && ocorrencia.longitude) {
                    const marker = createMapMarker(ocorrencia);
                    if (marker) {
                        state.markersLayer.addLayer(marker);
                        state.markers.push({ marker, ocorrencia });
                        console.log('✅ Marcador adicionado:', ocorrencia.codigo);
                    } else {
                        console.warn('❌ Falha ao criar marcador:', ocorrencia.codigo);
                    }
                } else {
                    console.warn('⚠️ Ocorrência sem coordenadas:', ocorrencia.codigo);
                }
            });
            
            // Mostrar informações sobre o carregamento
            console.log(`✅ ${state.ocorrencias.length} ocorrências carregadas`, result.info);
            
            // Mostrar mensagem baseada no tipo de usuário
            if (result.info) {
                let message = '';
                switch (result.info.tipo_usuario) {
                    case 'admin':
                        message = `Visualizando todas as ${result.info.total} ocorrências (Modo Administrador)`;
                        break;
                    case 'cidadao':
                        message = `Visualizando suas ${result.info.total} ocorrências`;
                        break;
                    case 'publico':
                        message = `Visualizando ${result.info.total} ocorrências públicas`;
                        break;
                }
                
                if (message) {
                    showMapMessage(message);
                }
            }
            
            console.log('📍 Ocorrências carregadas com sucesso:', state.ocorrencias.length);
        } else {
            console.error('Erro ao carregar ocorrências:', result.message);
            showMessage('error', 'Erro ao carregar ocorrências');
        }
    } catch (error) {
        console.error('Erro ao buscar ocorrências:', error);
        showMessage('error', 'Erro de conexão ao buscar ocorrências');
    }
}

function showMapMessage(message) {
    // Procurar por elemento existente ou criar um novo
    let messageEl = document.getElementById('map-message');
    
    if (!messageEl) {
        messageEl = document.createElement('div');
        messageEl.id = 'map-message';
        messageEl.className = 'map-message';
        
        const mapContainer = document.querySelector('.mapa-container');
        if (mapContainer) {
            mapContainer.insertBefore(messageEl, mapContainer.firstChild);
        }
    }
    
    messageEl.innerHTML = `
        <div class="message-content">
            <i class="fas fa-info-circle"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Auto-hide depois de 5 segundos
    setTimeout(() => {
        if (messageEl && messageEl.parentNode) {
            messageEl.style.opacity = '0';
            setTimeout(() => {
                if (messageEl && messageEl.parentNode) {
                    messageEl.parentNode.removeChild(messageEl);
                }
            }, 300);
        }
    }, 5000);
}

function loadSampleOcorrencias() {
    // Dados de exemplo de ocorrências em Santarém (COORDENADAS URBANAS CORRETAS)
    const sampleOcorrencias = [
        {
            id: 'STM001',
            codigo: 'STM001',
            tipo: 'buraco',
            descricao: 'Buraco grande na via principal',
            status: 'pendente',
            endereco: 'Av. Tapajós, Centro',
            coordenadas: [-2.419444, -54.708333],
            latitude: -2.419444,
            longitude: -54.708333,
            data_criacao: '2024-10-15',
            nome_cidadao: 'João Silva'
        },
        {
            id: 'STM002',
            codigo: 'STM002',
            tipo: 'lixo',
            descricao: 'Acúmulo de lixo na esquina',
            status: 'em_andamento',
            endereco: 'Rua Floriano Peixoto, Centro',
            coordenadas: [-2.421111, -54.705833],
            latitude: -2.421111,
            longitude: -54.705833,
            data_criacao: '2024-10-14',
            nome_cidadao: 'Maria Santos'
        },
        {
            id: 'STM003',
            codigo: 'STM003',
            tipo: 'iluminacao',
            descricao: 'Poste de luz queimado',
            status: 'concluida',
            endereco: 'Praça Tiradentes, Centro',
            coordenadas: [-2.418889, -54.707222],
            latitude: -2.418889,
            longitude: -54.707222,
            data_criacao: '2024-10-13',
            nome_cidadao: 'Carlos Oliveira'
        }
    ];

    state.ocorrencias = sampleOcorrencias;
    renderMapMarkers(sampleOcorrencias);
}

function renderMapMarkers(ocorrencias) {
    // Limpar marcadores existentes
    state.markersLayer.clearLayers();
    state.markers = [];

    ocorrencias.forEach(ocorrencia => {
        const marker = createMapMarker(ocorrencia);
        if (marker) {
            state.markersLayer.addLayer(marker);
            state.markers.push({ marker, ocorrencia });
        }
    });
}

function setupMapEvents() {
    if (!state.map) return;

    // Clique no mapa para selecionar localização
    state.map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        
        // Remover marcador temporário anterior
        if (state.tempMarker) {
            state.map.removeLayer(state.tempMarker);
        }
        
        // Criar novo marcador temporário
        state.tempMarker = L.marker([lat, lng], {
            icon: createTempIcon()
        }).addTo(state.map);
        
        // Salvar localização selecionada
        state.selectedLocation = { lat, lng };
        
        // Atualizar campos de coordenadas se existirem
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        if (latInput) latInput.value = lat.toFixed(6);
        if (lngInput) lngInput.value = lng.toFixed(6);
        
        // Atualizar campo de endereço com geocodificação reversa
        const enderecoInput = document.getElementById('endereco');
        if (enderecoInput) {
            reverseGeocode(lat, lng).then(address => {
                if (address) {
                    enderecoInput.value = address;
                } else {
                    enderecoInput.value = `Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`;
                }
            });
        }
        
        // Mostrar feedback visual
        showMessage('info', `Localização selecionada: ${lat.toFixed(6)}, ${lng.toFixed(6)}`);
    });
}

// Função para geocodificação reversa (converter coordenadas em endereço)
async function reverseGeocode(lat, lng) {
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1&accept-language=pt-BR`);
        const data = await response.json();
        
        if (data && data.display_name) {
            // Extrair partes relevantes do endereço
            const address = data.address || {};
            let formattedAddress = '';
            
            if (address.road) {
                formattedAddress += address.road;
                if (address.house_number) {
                    formattedAddress += ', ' + address.house_number;
                }
            } else if (address.pedestrian || address.path) {
                formattedAddress += address.pedestrian || address.path;
            }
            
            if (address.suburb || address.neighbourhood) {
                formattedAddress += ', ' + (address.suburb || address.neighbourhood);
            }
            
            if (address.city || address.town) {
                formattedAddress += ', ' + (address.city || address.town);
            } else {
                formattedAddress += ', Santarém';
            }
            
            if (address.state) {
                formattedAddress += ', ' + address.state;
            } else {
                formattedAddress += ', PA';
            }
            
            return formattedAddress || data.display_name;
        }
        
        return null;
    } catch (error) {
        console.warn('Erro na geocodificação reversa:', error);
        return null;
    }
}

function createTempIcon() {
    return L.divIcon({
        html: '<i class="fas fa-map-marker-alt" style="color: #ff6b6b; font-size: 24px;"></i>',
        iconSize: [24, 24],
        iconAnchor: [12, 24],
        className: 'temp-marker'
    });
}

function createMapMarker(ocorrencia) {
    console.log('🎯 Criando marcador para:', ocorrencia.codigo, ocorrencia);
    
    // Verificar coordenadas - podem vir como string ou number
    let lat = ocorrencia.latitude || ocorrencia.coordenadas?.[0];
    let lng = ocorrencia.longitude || ocorrencia.coordenadas?.[1];
    
    // Converter para número se necessário
    lat = parseFloat(lat);
    lng = parseFloat(lng);
    
    console.log('📍 Coordenadas processadas:', lat, lng);
    
    if (!lat || !lng || isNaN(lat) || isNaN(lng)) {
        console.warn('❌ Coordenadas inválidas para ocorrência:', ocorrencia.codigo, lat, lng);
        return null;
    }

    const icon = createMarkerIcon(ocorrencia.status, ocorrencia.tipo);
    
    console.log('🎨 Criando marcador com icon:', icon);
    
    const marker = L.marker([lat, lng], { icon })
        .bindPopup(createPopupContent(ocorrencia), {
            maxWidth: 300,
            className: 'custom-popup'
        });

    console.log('✅ Marcador criado com sucesso:', marker);
    return marker;
}

function createMarkerIcon(status, tipo) {
    const colors = {
        'pendente': '#ffd700',
        'em_analise': '#ff8c00',
        'em_andamento': '#1e90ff',
        'concluida': '#32cd32',
        'cancelada': '#dc143c'
    };

    const icons = {
        'buraco': 'fas fa-road',
        'iluminacao': 'fas fa-lightbulb',
        'lixo': 'fas fa-trash',
        'agua': 'fas fa-tint',
        'esgoto': 'fas fa-water',
        'calcada': 'fas fa-walking',
        'sinalizacao': 'fas fa-traffic-light',
        'outro': 'fas fa-exclamation-circle'
    };

    const color = colors[status] || '#gray';
    const iconClass = icons[tipo] || 'fas fa-map-marker-alt';

    return L.divIcon({
        html: `<i class="${iconClass}" style="color: ${color}; font-size: 20px;"></i>`,
        iconSize: [20, 20],
        iconAnchor: [10, 20],
        className: 'custom-marker'
    });
}

function createPopupContent(ocorrencia) {
    const statusLabels = {
        'pendente': 'Pendente',
        'em_analise': 'Em Análise', 
        'em_andamento': 'Em Andamento',
        'concluida': 'Concluída',
        'cancelada': 'Cancelada'
    };

    const tipoLabels = {
        'buraco': 'Buraco na Via',
        'iluminacao': 'Iluminação Pública',
        'lixo': 'Limpeza Urbana',
        'agua': 'Abastecimento de Água',
        'esgoto': 'Esgoto',
        'calcada': 'Calçada',
        'sinalizacao': 'Sinalização',
        'outro': 'Outros'
    };

    return `
        <div class="marker-popup">
            <h4>${tipoLabels[ocorrencia.tipo] || ocorrencia.tipo}</h4>
            <p><strong>Código:</strong> ${ocorrencia.codigo || ocorrencia.id}</p>
            <p><strong>Status:</strong> <span class="status-badge status-${ocorrencia.status}">${statusLabels[ocorrencia.status] || ocorrencia.status}</span></p>
            <p><strong>Endereço:</strong> ${ocorrencia.endereco}</p>
            <p><strong>Descrição:</strong> ${ocorrencia.descricao}</p>
            <p><strong>Data:</strong> ${new Date(ocorrencia.data_criacao || ocorrencia.data).toLocaleDateString('pt-BR')}</p>
            <p><strong>Cidadão:</strong> ${ocorrencia.nome_cidadao || ocorrencia.cidadao}</p>
        </div>
    `;
}

function addLocationControl() {
    if (!state.map) return;

    const locationControl = L.control({ position: 'topright' });
    
    locationControl.onAdd = function() {
        const div = L.DomUtil.create('div', 'leaflet-control-location');
        div.innerHTML = '<button class="location-btn" title="Minha Localização"><i class="fas fa-crosshairs"></i></button>';
        
        div.onclick = function() {
            getUserLocation();
        };
        
        return div;
    };
    
    locationControl.addTo(state.map);
}

function getUserLocation() {
    if (!navigator.geolocation) {
        showMessage('error', 'Geolocalização não é suportada pelo seu navegador');
        return;
    }

    const locationBtn = document.querySelector('.location-btn');
    if (locationBtn) {
        locationBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            state.map.setView([lat, lng], 16);
            
            const userMarker = L.marker([lat, lng], {
                icon: L.divIcon({
                    html: '<i class="fas fa-user-circle" style="color: #007bff; font-size: 24px;"></i>',
                    iconSize: [24, 24],
                    iconAnchor: [12, 24],
                    className: 'user-location-marker'
                })
            }).addTo(state.map);
            
            userMarker.bindPopup('Sua localização atual').openPopup();
            
            if (locationBtn) {
                locationBtn.innerHTML = '<i class="fas fa-crosshairs"></i>';
            }
            
            showMessage('success', 'Localização encontrada!');
        },
        function(error) {
            if (locationBtn) {
                locationBtn.innerHTML = '<i class="fas fa-crosshairs"></i>';
            }
            
            let message = 'Erro ao obter localização: ';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    message += 'Permissão negada pelo usuário';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message += 'Informações de localização não disponíveis';
                    break;
                case error.TIMEOUT:
                    message += 'Tempo limite excedido';
                    break;
                default:
                    message += 'Erro desconhecido';
                    break;
            }
            showMessage('error', message);
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 300000
        }
    );
}

// Handlers de formulários
async function handleOcorrenciaSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitButton = e.target.querySelector('.btn-submit');
    
    // Validar se uma localização foi selecionada
    if (!state.selectedLocation) {
        showMessage('error', 'Por favor, clique no mapa para selecionar a localização da ocorrência.');
        return;
    }
    
    // Mostrar loading
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<div class="loading"></div> Enviando...';
    submitButton.disabled = true;
    
    try {
        // Preparar dados para envio
        const data = {
            tipo: formData.get('tipo'),
            descricao: formData.get('descricao'),
            endereco: formData.get('endereco'),
            latitude: state.selectedLocation.lat,
            longitude: state.selectedLocation.lng,
            nome_cidadao: formData.get('nome'),
            email_cidadao: formData.get('email') || ''
        };
        
        // Enviar para API
        const response = await fetch(CONFIG.API_ENDPOINTS.ocorrencias, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            const novaOcorrencia = {
                id: result.data.id,
                codigo: result.data.codigo,
                tipo: data.tipo,
                descricao: data.descricao,
                endereco: data.endereco,
                coordenadas: [data.latitude, data.longitude],
                latitude: data.latitude,
                longitude: data.longitude,
                status: 'pendente',
                data_criacao: result.data.data_criacao,
                nome_cidadao: data.nome_cidadao,
                email_cidadao: data.email_cidadao
            };
            
            // Adicionar à lista de ocorrências
            state.ocorrencias.push(novaOcorrencia);
            
            // Adicionar marcador no mapa
            const newMarker = createMapMarker(novaOcorrencia);
            if (newMarker) {
                state.markersLayer.addLayer(newMarker);
                state.markers.push({ marker: newMarker, ocorrencia: novaOcorrencia });
            }
            
            // Remover marcador temporário
            if (state.tempMarker) {
                state.map.removeLayer(state.tempMarker);
                state.tempMarker = null;
            }
            
            // Centralizar mapa na nova ocorrência
            state.map.setView([novaOcorrencia.latitude, novaOcorrencia.longitude], 16);
            
            // Abrir popup do novo marcador
            setTimeout(() => {
                newMarker.openPopup();
            }, 500);
            
            showMessage('success', `Ocorrência registrada com sucesso! Código: ${result.data.codigo}`);
            e.target.reset();
            state.selectedLocation = null;
            
            // Atualizar resultado de rastreamento
            updateTrackingResult(novaOcorrencia);
            
        } else {
            throw new Error(result.message || 'Erro desconhecido');
        }
        
    } catch (error) {
        console.error('Erro ao registrar ocorrência:', error);
        showMessage('error', 'Erro ao registrar ocorrência: ' + error.message);
    } finally {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }
}

async function handleRastreamentoSubmit(e) {
    e.preventDefault();
    
    const codigo = document.getElementById('id-ocorrencia').value.trim();
    const resultadoElement = document.getElementById('resultado-rastreamento');
    
    if (!codigo) {
        showMessage('error', 'Digite o código da ocorrência');
        return;
    }
    
    try {
        // Mostrar loading
        resultadoElement.innerHTML = `
            <div class="loading-container">
                <div class="loading-spinner"></div>
                <p>Buscando informações da ocorrência...</p>
            </div>
        `;
        resultadoElement.style.display = 'block';
        
        // Buscar na API
        const response = await fetch(`${CONFIG.API_ENDPOINTS.rastreamento}?codigo=${encodeURIComponent(codigo)}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const ocorrencia = result.data;
            updateTrackingResult(ocorrencia);
            
            // Centralizar mapa na ocorrência se existir coordenadas
            if (ocorrencia.latitude && ocorrencia.longitude && state.map) {
                const lat = parseFloat(ocorrencia.latitude);
                const lng = parseFloat(ocorrencia.longitude);
                
                // Mover mapa para a localização
                state.map.setView([lat, lng], 16);
                
                // Criar marcador temporário se não existir
                if (state.tempTrackingMarker) {
                    state.map.removeLayer(state.tempTrackingMarker);
                }
                
                state.tempTrackingMarker = L.marker([lat, lng], {
                    icon: L.divIcon({
                        className: 'tracking-marker',
                        html: '<i class="fas fa-map-marker-alt"></i>',
                        iconSize: [30, 30],
                        iconAnchor: [15, 30]
                    })
                }).addTo(state.map).bindPopup(`
                    <strong>Ocorrência ${ocorrencia.codigo}</strong><br>
                    ${ocorrencia.endereco}
                `).openPopup();
                
                // Scroll suave para o mapa
                document.getElementById('mapa').scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
            }
        } else {
            resultadoElement.innerHTML = `
                <div class="tracking-result error">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h4>Ocorrência não encontrada</h4>
                    <p>O código <strong>"${codigo}"</strong> não foi encontrado em nosso sistema.</p>
                    <div class="error-suggestions">
                        <p><strong>Sugestões:</strong></p>
                        <ul>
                            <li>Verifique se o código foi digitado corretamente</li>
                            <li>O código deve ter o formato STM000000</li>
                            <li>Entre em contato conosco se o problema persistir</li>
                        </ul>
                    </div>
                </div>
            `;
        }
        
    } catch (error) {
        console.error('Erro ao buscar ocorrência:', error);
        resultadoElement.innerHTML = `
            <div class="tracking-result error">
                <div class="error-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h4>Erro ao buscar informações</h4>
                <p>Houve um problema ao buscar as informações da ocorrência.</p>
                <p>Tente novamente em alguns instantes.</p>
            </div>
        `;
    }
}

function updateTrackingResult(ocorrencia) {
    const resultadoElement = document.getElementById('resultado-rastreamento');
    
    // Mapa de status com cores e ícones
    const statusMap = {
        'pendente': { 
            label: 'Pendente', 
            class: 'pendente', 
            icon: 'fas fa-clock',
            description: 'Aguardando análise da equipe técnica'
        },
        'em_andamento': { 
            label: 'Em Andamento', 
            class: 'em-andamento', 
            icon: 'fas fa-tools',
            description: 'Equipe está trabalhando na resolução'
        },
        'concluida': { 
            label: 'Concluída', 
            class: 'concluida', 
            icon: 'fas fa-check-circle',
            description: 'Problema resolvido com sucesso!'
        },
        'cancelada': { 
            label: 'Cancelada', 
            class: 'cancelada', 
            icon: 'fas fa-times-circle',
            description: 'Ocorrência cancelada'
        }
    };
    
    const statusInfo = statusMap[ocorrencia.status] || { 
        label: ocorrencia.status, 
        class: 'pendente', 
        icon: 'fas fa-question-circle',
        description: 'Status em análise'
    };
    
    // Formatação do tipo
    const tipoFormatado = ocorrencia.tipo.charAt(0).toUpperCase() + ocorrencia.tipo.slice(1).replace('_', ' ');
    
    resultadoElement.innerHTML = `
        <div class="tracking-result success">
            <div class="tracking-header">
                <div class="tracking-code">
                    <i class="fas fa-barcode"></i>
                    <span>Código: <strong>${ocorrencia.codigo}</strong></span>
                </div>
                <div class="tracking-status ${statusInfo.class}">
                    <i class="${statusInfo.icon}"></i>
                    <span>${statusInfo.label}</span>
                </div>
            </div>
            
            <div class="tracking-details">
                <div class="detail-section">
                    <h5><i class="fas fa-info-circle"></i> Informações da Ocorrência</h5>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Tipo:</label>
                            <span>${tipoFormatado}</span>
                        </div>
                        <div class="detail-item">
                            <label>Data de Registro:</label>
                            <span>${ocorrencia.data_criacao_formatada}</span>
                        </div>
                        <div class="detail-item">
                            <label>Endereço:</label>
                            <span>${ocorrencia.endereco}</span>
                        </div>
                        <div class="detail-item">
                            <label>Solicitante:</label>
                            <span>${ocorrencia.nome_cidadao || 'Anônimo'}</span>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h5><i class="fas fa-file-alt"></i> Descrição</h5>
                    <p class="description">${ocorrencia.descricao}</p>
                </div>
                
                ${ocorrencia.observacoes ? `
                <div class="detail-section">
                    <h5><i class="fas fa-comment"></i> Observações da Equipe</h5>
                    <p class="observations">${ocorrencia.observacoes}</p>
                </div>
                ` : ''}
                
                <div class="detail-section">
                    <h5><i class="fas fa-clock"></i> Tempo de Processamento</h5>
                    <p class="processing-time">
                        Tempo decorrido: <strong>${ocorrencia.tempo_processamento.tempo_decorrido}</strong>
                        ${ocorrencia.tempo_processamento.tempo_resolucao ? 
                            `<br>Resolvida em: <strong>${ocorrencia.tempo_processamento.tempo_resolucao}</strong>` : ''}
                    </p>
                </div>
            </div>
            
            <div class="status-timeline">
                <h5><i class="fas fa-route"></i> Acompanhamento</h5>
                <div class="timeline">
                    ${ocorrencia.timeline.map(item => `
                        <div class="timeline-item ${item.concluido ? 'completed' : ''} ${item.ativo ? 'active' : ''}">
                            <div class="timeline-marker" style="border-color: ${getTimelineColor(item.cor)}">
                                <i class="${item.icon}" style="color: ${getTimelineColor(item.cor)}"></i>
                            </div>
                            <div class="timeline-content">
                                <h6>${item.titulo}</h6>
                                <p>${item.descricao}</p>
                                ${item.data_formatada ? `<small>${item.data_formatada}</small>` : ''}
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
            
            <div class="tracking-actions">
                <button type="button" class="btn btn-secondary" onclick="clearTrackingResult()">
                    <i class="fas fa-search"></i> Nova Busca
                </button>
                ${ocorrencia.latitude && ocorrencia.longitude ? `
                <button type="button" class="btn btn-primary" onclick="focusOnMap(${ocorrencia.latitude}, ${ocorrencia.longitude})">
                    <i class="fas fa-map-marker-alt"></i> Ver no Mapa
                </button>
                ` : ''}
            </div>
        </div>
    `;
}

function getTimelineColor(cor) {
    const colors = {
        'blue': '#0B3A60',
        'orange': '#FFA500',
        'green': '#28a745',
        'red': '#dc3545',
        'gray': '#6c757d'
    };
    return colors[cor] || colors.blue;
}

function clearTrackingResult() {
    const resultadoElement = document.getElementById('resultado-rastreamento');
    resultadoElement.style.display = 'none';
    document.getElementById('id-ocorrencia').value = '';
    
    // Remover marcador temporário do mapa
    if (state.tempTrackingMarker && state.map) {
        state.map.removeLayer(state.tempTrackingMarker);
        state.tempTrackingMarker = null;
    }
}

function focusOnMap(lat, lng) {
    if (state.map) {
        state.map.setView([lat, lng], 18);
        document.getElementById('mapa').scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center' 
        });
    }
}

// Função para testar códigos de exemplo
function testarCodigo(codigo) {
    document.getElementById('id-ocorrencia').value = codigo;
    document.getElementById('form-rastreamento').dispatchEvent(new Event('submit'));
}

// Expor função globalmente
window.testarCodigo = testarCodigo;

function clearTrackingResult() {
    const resultadoElement = document.getElementById('resultado-rastreamento');
    const inputElement = document.getElementById('id-ocorrencia');
    
    resultadoElement.style.display = 'none';
    resultadoElement.innerHTML = '';
    inputElement.value = '';
    inputElement.focus();
    
    // Remover marcador temporário se existir
    if (state.tempTrackingMarker && state.map) {
        state.map.removeLayer(state.tempTrackingMarker);
        state.tempTrackingMarker = null;
    }
}

function focusOnMap(lat, lng) {
    if (state.map) {
        state.map.setView([lat, lng], 16);
        document.getElementById('mapa').scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center' 
        });
    }
}

async function handleContatoSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitButton = e.target.querySelector('.btn-submit');
    
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<div class="loading"></div> Enviando...';
    submitButton.disabled = true;
    
    try {
        // Preparar dados
        const data = {
            nome: formData.get('nome'),
            email: formData.get('email'),
            telefone: formData.get('telefone'),
            assunto: formData.get('assunto'),
            mensagem: formData.get('mensagem')
        };
        
        // Enviar para API
        const response = await fetch(CONFIG.API_ENDPOINTS.contato, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('success', 'Mensagem enviada com sucesso! Entraremos em contato em breve.');
            e.target.reset();
        } else {
            throw new Error(result.message || 'Erro ao enviar mensagem');
        }
        
    } catch (error) {
        console.error('Erro ao enviar contato:', error);
        showMessage('error', 'Erro ao enviar mensagem: ' + error.message);
    } finally {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }
}

async function handleLoginSubmit(e) {
    e.preventDefault();
    
    const usuario = document.getElementById('login-usuario').value;
    const senha = document.getElementById('login-senha').value;
    const submitButton = e.target.querySelector('.btn');
    
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<div class="loading"></div> Entrando...';
    submitButton.disabled = true;
    
    try {
        const response = await fetch(CONFIG.API_ENDPOINTS.login, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ usuario, senha })
        });
        
        const result = await response.json();
        
        if (result.success) {
            state.currentUser = result.user;
            state.isAuthenticated = true;
            showMessage('success', 'Login realizado com sucesso!');
            
            // Redirecionar ou atualizar interface
            setTimeout(() => {
                window.location.href = '/admin/dashboard.html';
            }, 1000);
        } else {
            throw new Error(result.message || 'Credenciais inválidas');
        }
        
    } catch (error) {
        console.error('Erro no login:', error);
        showMessage('error', 'Erro no login: ' + error.message);
    } finally {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }
}

function handlePhotoUpload(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    // Validar tipo de arquivo
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!allowedTypes.includes(file.type)) {
        showMessage('error', 'Apenas arquivos JPG, JPEG e PNG são permitidos');
        e.target.value = '';
        return;
    }
    
    // Validar tamanho (5MB)
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
        showMessage('error', 'O arquivo deve ter no máximo 5MB');
        e.target.value = '';
        return;
    }
    
    // Preview da imagem
    const reader = new FileReader();
    reader.onload = function(e) {
        const previewContainer = document.getElementById('photo-preview');
        if (previewContainer) {
            previewContainer.innerHTML = `
                <img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px; margin-top: 10px;">
            `;
        }
    };
    reader.readAsDataURL(file);
}

// Funções auxiliares
function showMessage(type, message) {
    // Remove mensagem anterior se existir
    const existingMessage = document.querySelector('.alert-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert-message alert-${type}`;
    alertDiv.innerHTML = `
        <div class="alert-content">
            <i class="fas fa-${getAlertIcon(type)}"></i>
            <span>${message}</span>
            <button class="alert-close">&times;</button>
        </div>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-remove após 5 segundos
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
    
    // Evento de fechar
    alertDiv.querySelector('.alert-close').addEventListener('click', () => {
        alertDiv.remove();
    });
}

function getAlertIcon(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-triangle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

function openModal(title, content) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>${title}</h2>
            ${content}
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.style.display = 'block';
    
    // Eventos de fechar
    modal.querySelector('.close-modal').onclick = () => closeModal(modal);
    modal.onclick = (e) => {
        if (e.target === modal) closeModal(modal);
    };
    
    // ESC key
    document.addEventListener('keydown', function escHandler(e) {
        if (e.key === 'Escape') {
            closeModal(modal);
            document.removeEventListener('keydown', escHandler);
        }
    });
}

function closeModal(modal) {
    modal.style.display = 'none';
    setTimeout(() => {
        if (modal.parentNode) {
            modal.remove();
        }
    }, 300);
}

function setupSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

function setupModalEvents() {
    // Eventos para fechar modais
    document.addEventListener('click', (e) => {
        // Fechar modal clicando no X
        if (e.target.classList.contains('close-modal')) {
            const modal = e.target.closest('.modal');
            if (modal) {
                hideModal(modal.id);
                // Garantir que o scroll seja restaurado
                ensurePageScroll();
            }
        }
        
        // Fechar modal clicando fora do conteúdo
        if (e.target.classList.contains('modal')) {
            hideModal(e.target.id);
            // Garantir que o scroll seja restaurado
            ensurePageScroll();
        }
    });

    // Fechar modal com ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal[style*="block"]');
            openModals.forEach(modal => {
                hideModal(modal.id);
            });
            // Garantir que o scroll seja restaurado
            ensurePageScroll();
        }
    });

    // Configurar formulário de login do cidadão
    const formLoginCidadao = document.getElementById('form-login-cidadao');
    if (formLoginCidadao) {
        formLoginCidadao.addEventListener('submit', handleCidadaoLoginSubmit);
    }
    
    // Eventos para abrir modais específicos
    const loginBtn = document.getElementById('btn-login');
    if (loginBtn) {
        loginBtn.addEventListener('click', () => {
            openModal('Login do Gestor', `
                <form id="form-login" class="modal-form">
                    <div class="form-group">
                        <label for="login-usuario">Usuário:</label>
                        <input type="text" id="login-usuario" name="usuario" required>
                    </div>
                    <div class="form-group">
                        <label for="login-senha">Senha:</label>
                        <input type="password" id="login-senha" name="senha" required>
                    </div>
                    <button type="submit" class="btn">Entrar</button>
                </form>
            `);
            
            // Reconfigurar evento do formulário
            setTimeout(() => {
                const modalForm = document.getElementById('form-login');
                if (modalForm) {
                    modalForm.addEventListener('submit', handleLoginSubmit);
                }
            }, 100);
        });
    }
}

function setupMapSearch() {
    const searchInput = document.getElementById('map-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            if (query.length < 3) {
                // Mostrar todos os marcadores
                state.markers.forEach(({ marker }) => {
                    state.markersLayer.addLayer(marker);
                });
                return;
            }
            
            // Filtrar marcadores
            state.markersLayer.clearLayers();
            const matchingMarkers = state.markers.filter(({ ocorrencia }) => {
                const searchableText = `${ocorrencia.tipo} ${ocorrencia.descricao} ${ocorrencia.endereco}`.toLowerCase();
                return searchableText.includes(query);
            });
            
            matchingMarkers.forEach(({ marker }) => {
                state.markersLayer.addLayer(marker);
            });
            
            if (matchingMarkers.length === 0) {
                showMessage('info', 'Nenhuma ocorrência encontrada para a busca.');
            } else {
                showMessage('success', `${matchingMarkers.length} ocorrência(s) encontrada(s).`);
                
                // Ajustar zoom para mostrar todos os resultados
                if (matchingMarkers.length === 1) {
                    const coords = matchingMarkers[0].ocorrencia.coordenadas;
                    state.map.setView(coords, 16);
                }
            }
        });
    }
}

function setupMapFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    if (filterButtons.length === 0) return;

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remover classe active de todos os botões
            filterButtons.forEach(btn => btn.classList.remove('active'));
            
            // Adicionar classe active ao botão clicado
            this.classList.add('active');
            
            // Obter filtro selecionado
            const filter = this.getAttribute('data-filter');
            
            // Aplicar filtro
            applyMapFilter(filter);
        });
    });
}

function applyMapFilter(filter) {
    // Limpar marcadores
    state.markersLayer.clearLayers();
    
    // Filtrar ocorrências
    let filteredOcorrencias = state.ocorrencias;
    
    if (filter !== 'all') {
        filteredOcorrencias = state.ocorrencias.filter(ocorrencia => {
            return ocorrencia.status === filter;
        });
    }
    
    // Adicionar marcadores filtrados
    filteredOcorrencias.forEach(ocorrencia => {
        const marker = createMapMarker(ocorrencia);
        if (marker) {
            state.markersLayer.addLayer(marker);
        }
    });
    
    // Atualizar contador
    const totalVisible = filteredOcorrencias.length;
    showMessage('info', `${totalVisible} ocorrência(s) ${filter === 'all' ? 'encontrada(s)' : 'filtrada(s)'}`);
}

// Função para carregar estatísticas da página inicial
async function loadEstatisticas() {
    console.log('📊 INICIANDO loadEstatisticas()');
    
    // Verificar se os elementos existem no HTML
    const elementos = ['ocorrencias-resolvidas', 'satisfacao', 'horas-medias'];
    elementos.forEach(id => {
        const element = document.getElementById(id);
        console.log(`🔍 Elemento ${id}:`, element ? 'ENCONTRADO' : 'NÃO ENCONTRADO');
        if (element) {
            console.log(`   Valor atual: "${element.textContent}"`);
        }
    });
    
    try {
        console.log('🔄 Fazendo requisição para API...');
        const response = await fetch('api/stats.php');
        
        console.log('📡 Resposta da API:', {
            status: response.status,
            ok: response.ok,
            statusText: response.statusText,
            url: response.url
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        console.log('📄 Texto bruto da resposta:', text);
        
        const data = JSON.parse(text);
        console.log('🔍 Dados parseados:', data);
        
        if (data.success && data.data) {
            console.log('✅ Dados válidos recebidos:', data.data);
            
            // Acessar os dados do resumo
            const resumo = data.data.resumo || {};
            console.log('📊 Dados do resumo:', resumo);
            
            // Atualizar estatísticas com os dados corretos da API
            updateStatElement('ocorrencias-resolvidas', resumo.concluidas || 0);
            updateStatElement('satisfacao', resumo.taxa_satisfacao || 0);
            updateStatElement('horas-medias', resumo.tempo_medio_resolucao || 0);
            
            console.log('✅ Estatísticas atualizadas com sucesso');
        } else {
            console.error('❌ API retornou erro:', data.message || 'Dados inválidos');
        }
    } catch (error) {
        console.error('💥 Erro detalhado ao carregar estatísticas:', {
            message: error.message,
            stack: error.stack,
            name: error.name
        });
    }
}

function updateStatElement(id, value) {
    const element = document.getElementById(id);
    console.log(`🔧 updateStatElement('${id}', '${value}')`);
    
    if (element) {
        console.log(`✅ Elemento ${id} encontrado. Valor atual: "${element.textContent}"`);
        
        // Determinar o formato baseado no ID
        let displayValue = value;
        if (id === 'satisfacao') {
            displayValue = value + '%';
        } else if (id === 'horas-medias') {
            displayValue = value;
        } else {
            displayValue = value;
        }
        
        // Para números, fazer animação
        const currentValue = parseInt(element.textContent.replace(/[^\d]/g, '')) || 0;
        const targetValue = parseInt(value) || 0;
        
        console.log(`🎯 Animando de ${currentValue} para ${targetValue}`);
        
        if (currentValue !== targetValue && id !== 'satisfacao') {
            // Animação simples para números
            animateCounter(element, currentValue, targetValue, 1000, id === 'satisfacao');
        } else {
            // Atualização direta
            element.textContent = displayValue;
        }
        
        console.log(`✅ ${id} atualizado para: "${displayValue}"`);
    } else {
        console.error(`❌ Elemento ${id} NÃO ENCONTRADO no DOM`);
        
        // Listar todos os elementos com IDs que começam com essas palavras
        const allElements = document.querySelectorAll('[id*="' + id.split('-')[0] + '"]');
        console.log(`🔍 Elementos similares encontrados:`, Array.from(allElements).map(el => el.id));
    }
}

function animateCounter(element, start, end, duration, isPercentage = false) {
    const startTime = performance.now();
    
    function updateCounter(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const current = Math.floor(start + (end - start) * progress);
        element.textContent = current + (isPercentage ? '%' : '');
        
        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        } else {
            // Garantir que o valor final seja exato
            element.textContent = end + (isPercentage ? '%' : '');
        }
    }
    
    requestAnimationFrame(updateCounter);
}

// Função para simular chamadas de API (fallback)
function simulateAPICall(delay = 1000) {
    return new Promise(resolve => setTimeout(resolve, delay));
}

// Função de validação de API
async function validateApiResponse(response) {
    if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    const data = await response.json();
    if (!data.success) {
        throw new Error(data.message || 'Erro na API');
    }
    
    return data;
}

// Debug helpers
window.cidadeAberta = {
    state,
    CONFIG,
    // Funções para debug
    showMarkers: () => console.log(state.markers),
    showOcorrencias: () => console.log(state.ocorrencias),
    centerMap: (lat, lng, zoom = 15) => state.map.setView([lat, lng], zoom),
    addTestMarker: (lat, lng) => {
        const marker = L.marker([lat, lng]).addTo(state.map);
        marker.bindPopup('Marcador de teste').openPopup();
    }
};

// Funções de suporte ao rastreamento
function setupTrackingInput() {
    const input = document.getElementById('id-ocorrencia');
    if (input) {
        input.addEventListener('input', function(e) {
            let valor = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            if (valor.length > 3 && !valor.startsWith('STM')) {
                valor = 'STM' + valor.slice(3);
            }
            e.target.value = valor;
        });
    }
}

function testarCodigo(codigo) {
    const input = document.getElementById('id-ocorrencia');
    if (input) {
        input.value = codigo;
        input.focus();
        
        // Disparar evento de submit do formulário
        const form = document.getElementById('form-rastreamento');
        if (form) {
            const event = new Event('submit', {
                bubbles: true,
                cancelable: true
            });
            form.dispatchEvent(event);
        }
    }
}

// Função global para testar códigos (usada nos botões HTML)
window.testarCodigo = testarCodigo;

// Funções de Autenticação e Sessão
async function checkUserSession() {
    try {
        const response = await fetch(CONFIG.API_ENDPOINTS.sessao);
        const result = await response.json();
        
        if (result.success && result.authenticated) {
            state.currentUser = result.user;
            state.isAuthenticated = true;
            state.userType = result.user.tipo;
            
            console.log('👤 Usuário autenticado:', result.user);
            updateUIForAuthenticatedUser();
        } else {
            state.currentUser = null;
            state.isAuthenticated = false;
            state.userType = null;
            updateUIForGuestUser();
        }
    } catch (error) {
        console.error('Erro ao verificar sessão:', error);
        state.currentUser = null;
        state.isAuthenticated = false;
        state.userType = null;
        updateUIForGuestUser();
    }
}

function updateUIForAuthenticatedUser() {
    const loginButton = document.querySelector('.btn-nav.btn-login');
    if (loginButton && state.currentUser) {
        if (state.userType === 'admin') {
            loginButton.innerHTML = `
                <i class="fas fa-user-shield"></i>
                <span>Admin: ${state.currentUser.nome}</span>
            `;
            loginButton.setAttribute('data-admin', 'true');
            loginButton.onclick = showAdminPanel;
        } else if (state.userType === 'cidadao') {
            loginButton.innerHTML = `
                <i class="fas fa-user"></i>
                <span>${state.currentUser.nome}</span>
            `;
            loginButton.removeAttribute('data-admin');
            loginButton.onclick = showCidadaoPanel;
        }
    }
    
    // Atualizar interface baseada no tipo de usuário
    if (state.userType === 'admin') {
        showAdminFeatures();
    } else if (state.userType === 'cidadao') {
        showCidadaoFeatures();
    }
}

function updateUIForGuestUser() {
    const loginButton = document.querySelector('.btn-nav.btn-login');
    if (loginButton) {
        loginButton.innerHTML = `
            <i class="fas fa-sign-in-alt"></i>
            <span>Entrar</span>
        `;
        loginButton.onclick = () => showModal('login-modal');
    }
    hideAdminFeatures();
}

function showAdminFeatures() {
    // Mostrar recursos administrativos
    console.log('👑 Modo Administrador ativado');
    
    // Carregar todas as ocorrências
    loadOcorrencias();
    
    // Adicionar botão de logout
    addLogoutButton();
}

function showCidadaoFeatures() {
    // Mostrar recursos do cidadão
    console.log('👤 Modo Cidadão ativado');
    
    // Carregar apenas ocorrências do cidadão
    loadOcorrencias();
    
    // Adicionar botão de logout
    addLogoutButton();
}

function hideAdminFeatures() {
    // Esconder recursos administrativos
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.remove();
    }
}

function addLogoutButton() {
    // Verificar se já existe
    if (document.getElementById('logout-btn')) return;
    
    const navbar = document.querySelector('.nav-menu');
    if (navbar) {
        const logoutItem = document.createElement('li');
        logoutItem.className = 'nav-item';
        logoutItem.innerHTML = `
            <a href="#" class="nav-link" id="logout-btn" onclick="logout()">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        `;
        navbar.appendChild(logoutItem);
    }
}

async function logout() {
    try {
        await fetch(CONFIG.API_ENDPOINTS.sessao, {
            method: 'POST'
        });
        
        // Limpar estado
        state.currentUser = null;
        state.isAuthenticated = false;
        state.userType = null;
        
        // Atualizar interface
        updateUIForGuestUser();
        
        // Recarregar ocorrências (modo público)
        loadOcorrencias();
        
        showMessage('success', 'Logout realizado com sucesso!');
        
    } catch (error) {
        console.error('Erro no logout:', error);
        showMessage('error', 'Erro ao fazer logout');
    }
}

function showAdminPanel() {
    // Criar modal com opções administrativas
    const modalHTML = `
        <div id="admin-options-modal" class="modal" style="display: block;">
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h2><i class="fas fa-user-shield"></i> Painel Administrativo</h2>
                    <span class="close" onclick="hideModal('admin-options-modal')">&times;</span>
                </div>
                <div class="modal-body">
                    <p><strong>Bem-vindo, ${state.currentUser.nome}!</strong></p>
                    <p>Escolha uma das opções administrativas:</p>
                    
                    <div style="display: grid; gap: 15px; margin: 20px 0;">
                        <button onclick="openAdminDashboard()" class="admin-option-btn">
                            <i class="fas fa-tachometer-alt"></i>
                            <div>
                                <strong>Painel Completo</strong>
                                <small>Gerenciamento de usuários, logs e configurações</small>
                            </div>
                        </button>
                        
                        <button onclick="showOcorrenciasAdmin()" class="admin-option-btn">
                            <i class="fas fa-list"></i>
                            <div>
                                <strong>Gerenciar Ocorrências</strong>
                                <small>Visualizar e atualizar todas as ocorrências</small>
                            </div>
                        </button>
                        
                        <button onclick="showRelatorios()" class="admin-option-btn">
                            <i class="fas fa-chart-bar"></i>
                            <div>
                                <strong>Relatórios</strong>
                                <small>Estatísticas e análises do sistema</small>
                            </div>
                        </button>
                        
                        <button onclick="logout()" class="admin-option-btn logout-btn">
                            <i class="fas fa-sign-out-alt"></i>
                            <div>
                                <strong>Sair</strong>
                                <small>Fazer logout do sistema</small>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal existente se houver
    const existingModal = document.getElementById('admin-options-modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Adicionar novo modal
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Bloquear scroll
    document.body.style.overflow = 'hidden';
}

function showCidadaoPanel() {
    // Mostrar painel do cidadão
    showMessage('info', 'Painel do cidadão em desenvolvimento. Use a consulta pública para acompanhar suas ocorrências.');
}

// Funções do painel administrativo
function openAdminDashboard() {
    hideModal('admin-options-modal');
    showMessage('info', 'Redirecionando para o painel administrativo...');
    
    // Redirecionar para o painel admin
    setTimeout(() => {
        window.open('./admin/index.html', '_blank');
    }, 1000);
}

function showOcorrenciasAdmin() {
    hideModal('admin-options-modal');
    showMessage('info', 'Redirecionando para gerenciamento de ocorrências...');
    
    // Redirecionar para a página de gerenciamento
    setTimeout(() => {
        window.open('./admin/gerenciar-ocorrencias.html', '_blank');
    }, 1000);
}

function showRelatorios() {
    hideModal('admin-options-modal');
    showMessage('info', 'Carregando relatórios e estatísticas...');
    
    // Mostrar estatísticas detalhadas
    loadEstatatisticasAdmin();
}

function addAdminControls() {
    // Adicionar controles administrativos à interface
    const header = document.querySelector('.header-stats');
    if (header && !document.getElementById('admin-controls')) {
        const adminControls = document.createElement('div');
        adminControls.id = 'admin-controls';
        adminControls.innerHTML = `
            <div style="background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 15px; margin: 10px 0; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
                <h4><i class="fas fa-user-shield"></i> Modo Administrativo</h4>
                <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
                    <button onclick="openAdminDashboard()" class="btn-admin-control">
                        <i class="fas fa-tachometer-alt"></i> Painel Completo
                    </button>
                    <button onclick="exportarDados()" class="btn-admin-control">
                        <i class="fas fa-download"></i> Exportar Dados
                    </button>
                    <button onclick="showBackup()" class="btn-admin-control">
                        <i class="fas fa-database"></i> Backup
                    </button>
                </div>
            </div>
        `;
        header.insertAdjacentElement('afterend', adminControls);
    }
}

function loadEstatatisticasAdmin() {
    // Carregar estatísticas administrativas detalhadas
    console.log('📊 Carregando estatísticas administrativas...');
    // Esta função pode ser expandida para mostrar relatórios detalhados
}

function exportarDados() {
    showMessage('info', 'Preparando exportação de dados...');
    // Implementar exportação de dados
}

function showBackup() {
    showMessage('info', 'Funcionalidade de backup em desenvolvimento...');
    // Implementar backup
}

// Função global para logout
window.logout = logout;

// Funções globais para o painel admin
window.openAdminDashboard = openAdminDashboard;
window.showOcorrenciasAdmin = showOcorrenciasAdmin;
window.showRelatorios = showRelatorios;
window.exportarDados = exportarDados;
window.showBackup = showBackup;

// Função de Login Unificado - HandleSubmit
async function handleLoginUnificadoSubmit(e) {
    e.preventDefault();
    
    const email = document.getElementById('login-email').value.trim();
    const senha = document.getElementById('login-senha').value;
    const lembrarLogin = document.getElementById('lembrar-login-unificado').checked;
    const submitButton = e.target.querySelector('.btn');
    
    // Validações de segurança
    if (!email) {
        showMessage('error', 'Digite seu email ou usuário');
        return;
    }
    
    if (!senha) {
        showMessage('error', 'Digite sua senha');
        return;
    }
    
    if (senha.length < 6) {
        showMessage('error', 'Senha deve ter pelo menos 6 caracteres');
        return;
    }
    
    // Verificar tentativas de login
    if (!checkLoginAttempts(email)) {
        showMessage('error', 'Muitas tentativas de login. Tente novamente em 15 minutos.');
        return;
    }
    
    let originalText;
    try {
        // Mostrar loading
        originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Entrando...';
        submitButton.disabled = true;
        
        const response = await fetch(CONFIG.API_ENDPOINTS.login, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: email,
                senha: senha,
                lembrar: lembrarLogin
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Login bem-sucedido
            clearLoginAttempts(email);
            
            // Atualizar estado da aplicação
            state.currentUser = result.user;
            state.isAuthenticated = true;
            state.userType = result.user_type;
            
            // Fechar modal
            hideModal('login-modal');
            
            // Atualizar interface baseada no tipo de usuário
            updateUIForAuthenticatedUser();
            
            // Recarregar dados baseado no tipo de usuário
            loadOcorrencias();
            
            // Mostrar mensagem de sucesso
            let welcomeMessage = `Bem-vindo, ${result.user.nome}!`;
            if (result.user_type === 'admin') {
                welcomeMessage += ' (Modo Administrativo)';
            }
            
            showMessage('success', welcomeMessage);
            
            console.log(`🎉 Login ${result.user_type} realizado:`, result.user);
            
        } else {
            // Login falhou
            recordFailedAttempt(email);
            showMessage('error', result.message);
        }
        
    } catch (error) {
        console.error('Erro no login:', error);
        recordFailedAttempt(email);
        showMessage('error', 'Erro ao conectar com o servidor. Tente novamente.');
        
    } finally {
        // Restaurar botão
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }
}

// Função de Login do Cidadão - HandleSubmit
async function handleCidadaoLoginSubmit(e) {
    e.preventDefault();
    
    const email = document.getElementById('cidadao-email').value.trim();
    const senha = document.getElementById('cidadao-senha').value;
    const lembrarLogin = document.getElementById('lembrar-login').checked;
    const submitButton = e.target.querySelector('.btn');
    
    // Validações de segurança
    if (!email) {
        showMessage('error', 'Digite seu email');
        return;
    }
    
    if (!isValidEmail(email)) {
        showMessage('error', 'Digite um email válido');
        return;
    }
    
    if (!senha) {
        showMessage('error', 'Digite sua senha');
        return;
    }
    
    if (senha.length < 6) {
        showMessage('error', 'Senha deve ter pelo menos 6 caracteres');
        return;
    }
    
    // Verificar tentativas de login
    if (!checkLoginAttempts(email)) {
        return;
    }
    
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<div class="loading-spinner"></div> Verificando...';
    submitButton.disabled = true;
    
    try {
        const response = await fetch(CONFIG.API_ENDPOINTS.loginCidadao, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email: email,
                senha: hashPassword(senha),
                lembrar: lembrarLogin
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Limpar tentativas de login
            clearLoginAttempts(email);
            
            // Atualizar estado do usuário
            state.currentUser = result.data;
            state.isAuthenticated = true;
            state.userType = 'cidadao';
            
            // Fechar modal
            hideModal('cidadao-modal');
            
            // Atualizar interface
            updateUIForAuthenticatedUser();
            
            // Carregar ocorrências do cidadão
            loadOcorrencias();
            
            showMessage('success', `Bem-vindo(a), ${result.data.nome}! Você tem ${result.data.total_ocorrencias} ocorrência(s) registrada(s).`);
            
            // Limpar formulário
            document.getElementById('form-login-cidadao').reset();
            
        } else {
            // Registrar tentativa falhada
            recordFailedAttempt(email);
            
            const attemptsLeft = MAX_ATTEMPTS - (loginAttempts[email]?.count || 0);
            if (attemptsLeft > 0) {
                showMessage('error', `${result.message}. Tentativas restantes: ${attemptsLeft}`);
            } else {
                showMessage('error', 'Conta temporariamente bloqueada por segurança.');
            }
        }
        
    } catch (error) {
        console.error('Erro no login do cidadão:', error);
        showMessage('error', 'Erro ao fazer login. Tente novamente.');
    } finally {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }
}

// Função para recuperação de senha
async function handleRecuperarSenhaSubmit(e) {
    e.preventDefault();
    
    const email = document.getElementById('recuperar-email').value.trim();
    const submitButton = e.target.querySelector('.btn');
    
    if (!isValidEmail(email)) {
        showMessage('error', 'Digite um email válido');
        return;
    }
    
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<div class="loading-spinner"></div> Enviando...';
    submitButton.disabled = true;
    
    try {
        const response = await fetch('./api/recuperar_senha.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('success', 'Instruções enviadas para seu email!');
            hideModal('recuperar-senha-modal');
            document.getElementById('form-recuperar-senha').reset();
        } else {
            showMessage('error', result.message);
        }
        
    } catch (error) {
        console.error('Erro na recuperação:', error);
        showMessage('error', 'Erro ao enviar instruções. Tente novamente.');
    } finally {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }
}

// Função para cadastro
async function handleCadastroSubmit(e) {
    e.preventDefault();
    
    const nome = document.getElementById('cadastro-nome').value.trim();
    const email = document.getElementById('cadastro-email').value.trim();
    const senha = document.getElementById('cadastro-senha').value;
    const confirmarSenha = document.getElementById('cadastro-confirmar-senha').value;
    const aceitarTermos = document.getElementById('aceitar-termos').checked;
    const submitButton = e.target.querySelector('.btn');
    
    // Validações
    if (!nome || nome.length < 2) {
        showMessage('error', 'Digite seu nome completo');
        return;
    }
    
    if (!isValidEmail(email)) {
        showMessage('error', 'Digite um email válido');
        return;
    }
    
    if (senha.length < 8) {
        showMessage('error', 'Senha deve ter pelo menos 8 caracteres');
        return;
    }
    
    if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(senha)) {
        showMessage('error', 'Senha deve conter maiúscula, minúscula e número');
        return;
    }
    
    if (senha !== confirmarSenha) {
        showMessage('error', 'Senhas não coincidem');
        return;
    }
    
    if (!aceitarTermos) {
        showMessage('error', 'Aceite os termos de uso para continuar');
        return;
    }
    
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<div class="loading-spinner"></div> Cadastrando...';
    submitButton.disabled = true;
    
    try {
        const response = await fetch('./api/cadastro_cidadao.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nome,
                email,
                senha: hashPassword(senha)
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('success', 'Cadastro realizado com sucesso! Faça login para continuar.');
            hideModal('cadastro-modal');
            showCidadaoLoginModal();
            document.getElementById('form-cadastro-cidadao').reset();
        } else {
            showMessage('error', result.message);
        }
        
    } catch (error) {
        console.error('Erro no cadastro:', error);
        showMessage('error', 'Erro ao criar conta. Tente novamente.');
    } finally {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }
}

// === ENHANCED ANIMATIONS AND ACCESSIBILITY === //

// Animation Observer for scroll-triggered animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const animationObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const element = entry.target;
            
            // Add animation class based on data attribute
            const animationType = element.dataset.animation;
            if (animationType) {
                element.classList.add(`animate-${animationType}`);
            }
            
            // Reveal elements
            if (element.classList.contains('reveal')) {
                element.classList.add('active');
            }
            
            // Staggered animations
            if (element.classList.contains('stagger-animation')) {
                element.classList.add('animate');
            }
            
            // Stop observing this element
            animationObserver.unobserve(element);
        }
    });
}, observerOptions);

// Initialize animations on page load
function initAnimations() {
    // Observe elements with animation data attributes
    document.querySelectorAll('[data-animation]').forEach(el => {
        animationObserver.observe(el);
    });
    
    // Observe reveal elements
    document.querySelectorAll('.reveal').forEach(el => {
        animationObserver.observe(el);
    });
    
    // Observe stagger animation containers
    document.querySelectorAll('.stagger-animation').forEach(el => {
        animationObserver.observe(el);
    });
    
    // Add page enter animation
    document.body.classList.add('page-enter');
    
    // Add hover animations to cards
    document.querySelectorAll('.card').forEach(card => {
        card.classList.add('hover-lift');
    });
    
    // Add hover animations to buttons
    document.querySelectorAll('.btn').forEach(btn => {
        btn.classList.add('hover-scale');
    });
}

// Enhanced Form Validation with Accessibility
function validateFormField(field) {
    const value = field.value.trim();
    const fieldName = field.name;
    const errorElement = document.getElementById(`${field.id}-error`);
    let isValid = true;
    let errorMessage = '';
    
    // Clear previous error state
    field.setAttribute('aria-invalid', 'false');
    if (errorElement) {
        errorElement.textContent = '';
    }
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'Este campo é obrigatório.';
    }
    
    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Por favor, insira um e-mail válido.';
        }
    }
    
    // Password validation
    if (field.type === 'password' && value) {
        if (value.length < 6) {
            isValid = false;
            errorMessage = 'A senha deve ter pelo menos 6 caracteres.';
        }
    }
    
    // Phone validation (Brazilian format)
    if (field.type === 'tel' && value) {
        const phoneRegex = /^\(\d{2}\)\s?\d{4,5}-?\d{4}$/;
        if (!phoneRegex.test(value)) {
            isValid = false;
            errorMessage = 'Por favor, insira um telefone válido. Ex: (93) 99999-9999';
        }
    }
    
    // Update field state
    if (!isValid) {
        field.setAttribute('aria-invalid', 'true');
        if (errorElement) {
            errorElement.textContent = errorMessage;
            // Announce error to screen readers
            errorElement.setAttribute('aria-live', 'polite');
        }
        field.classList.add('error');
    } else {
        field.classList.remove('error');
    }
    
    return isValid;
}

// Form Validation Setup
function setupFormValidation() {
    // Add real-time validation to all form inputs
    document.querySelectorAll('input, select, textarea').forEach(field => {
        field.addEventListener('blur', () => validateFormField(field));
        field.addEventListener('input', () => {
            // Clear error state on input
            if (field.getAttribute('aria-invalid') === 'true') {
                validateFormField(field);
            }
        });
    });
    
    // Enhanced form submission
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', (e) => {
            let isFormValid = true;
            
            // Validate all fields
            form.querySelectorAll('input, select, textarea').forEach(field => {
                if (!validateFormField(field)) {
                    isFormValid = false;
                }
            });
            
            if (!isFormValid) {
                e.preventDefault();
                
                // Focus first invalid field
                const firstInvalidField = form.querySelector('[aria-invalid="true"]');
                if (firstInvalidField) {
                    firstInvalidField.focus();
                    // Add shake animation to form
                    form.style.animation = 'shake 0.5s ease-in-out';
                    setTimeout(() => {
                        form.style.animation = '';
                    }, 500);
                }
                
                // Announce validation errors
                const errorCount = form.querySelectorAll('[aria-invalid="true"]').length;
                announceToScreenReader(`Formulário contém ${errorCount} erro${errorCount > 1 ? 's' : ''}. Por favor, corrija os campos destacados.`);
            }
        });
    });
}

// Accessibility Announcements
function announceToScreenReader(message) {
    // Create live region if it doesn't exist
    let liveRegion = document.getElementById('sr-live-region');
    if (!liveRegion) {
        liveRegion = document.createElement('div');
        liveRegion.id = 'sr-live-region';
        liveRegion.setAttribute('aria-live', 'polite');
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.className = 'sr-only';
        document.body.appendChild(liveRegion);
    }
    
    // Clear and set new message
    liveRegion.textContent = '';
    setTimeout(() => {
        liveRegion.textContent = message;
    }, 100);
}

// Enhanced Modal Accessibility
function enhanceModalAccessibility() {
    document.querySelectorAll('.modal').forEach(modal => {
        // Set initial ARIA attributes
        modal.setAttribute('aria-hidden', 'true');
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        
        // Find modal content for labeling
        const modalTitle = modal.querySelector('h2, h3, .modal-title');
        if (modalTitle && !modalTitle.id) {
            modalTitle.id = `${modal.id}-title`;
        }
        if (modalTitle) {
            modal.setAttribute('aria-labelledby', modalTitle.id);
        }
        
        // Add close button functionality
        const closeBtn = modal.querySelector('.close-modal');
        if (closeBtn) {
            closeBtn.setAttribute('aria-label', 'Fechar modal');
            closeBtn.addEventListener('click', () => hideModal(modal.id));
        }
        
        // Close on Escape key
        modal.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                hideModal(modal.id);
            }
        });
        
        // Trap focus within modal
        modal.addEventListener('keydown', trapFocus);
    });
}

// Focus Management
function trapFocus(e) {
    if (e.key !== 'Tab') return;
    
    const modal = e.currentTarget;
    const focusableElements = modal.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    const firstFocusable = focusableElements[0];
    const lastFocusable = focusableElements[focusableElements.length - 1];
    
    if (e.shiftKey) {
        if (document.activeElement === firstFocusable) {
            e.preventDefault();
            lastFocusable.focus();
        }
    } else {
        if (document.activeElement === lastFocusable) {
            e.preventDefault();
            firstFocusable.focus();
        }
    }
}

// Enhanced Modal Functions
function showModalWithAccessibility(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    // Store currently focused element
    modal.dataset.previousFocus = document.activeElement;
    
    // Show modal
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    
    // Add animation
    modal.classList.add('animate-fadeIn');
    modal.querySelector('.modal-content').classList.add('animate-slideInDown');
    
    // Focus first focusable element
    setTimeout(() => {
        const firstFocusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (firstFocusable) {
            firstFocusable.focus();
        }
    }, 100);
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

function hideModalWithAccessibility(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    // Hide modal
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    
    // Restore body scroll
    document.body.style.overflow = '';
    
    // Return focus to previously focused element
    const previousFocus = document.querySelector(modal.dataset.previousFocus);
    if (previousFocus) {
        previousFocus.focus();
    }
}

// Progress Indicator for Loading States
function showLoadingState(element, message = 'Carregando...') {
    element.classList.add('loading');
    element.setAttribute('aria-busy', 'true');
    element.setAttribute('aria-label', message);
    
    // Disable interactive elements
    const interactiveElements = element.querySelectorAll('button, input, select, textarea, a');
    interactiveElements.forEach(el => {
        el.disabled = true;
        el.setAttribute('tabindex', '-1');
    });
}

function hideLoadingState(element) {
    element.classList.remove('loading');
    element.removeAttribute('aria-busy');
    element.removeAttribute('aria-label');
    
    // Re-enable interactive elements
    const interactiveElements = element.querySelectorAll('button, input, select, textarea, a');
    interactiveElements.forEach(el => {
        el.disabled = false;
        el.removeAttribute('tabindex');
    });
}

// Enhanced Navigation
function enhanceNavigation() {
    // Add current page indicator
    const currentPage = window.location.hash || '#home';
    document.querySelectorAll('nav a[href^="#"]').forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.setAttribute('aria-current', 'page');
            link.classList.add('active');
        }
    });
    
    // Smooth scroll with accessibility announcements
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', (e) => {
            const targetId = link.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                e.preventDefault();
                
                // Smooth scroll
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Update focus and announce
                setTimeout(() => {
                    targetElement.focus();
                    const sectionTitle = targetElement.querySelector('h1, h2, h3, .section-title');
                    if (sectionTitle) {
                        announceToScreenReader(`Navegou para seção: ${sectionTitle.textContent}`);
                    }
                }, 500);
            }
        });
    });
}

// Initialize all enhancements
function initEnhancements() {
    initAnimations();
    setupFormValidation();
    enhanceModalAccessibility();
    enhanceNavigation();
    
    // Add animation classes to existing elements
    setTimeout(() => {
        document.querySelectorAll('.card').forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('animate-fadeInUp');
        });
        
        document.querySelectorAll('.feature-item').forEach((item, index) => {
            item.style.animationDelay = `${index * 0.15}s`;
            item.classList.add('animate-fadeInRight');
        });
    }, 500);
    
    console.log('🎨 Animations and accessibility enhancements initialized');
}

// Call initialization when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEnhancements);
} else {
    initEnhancements();
}