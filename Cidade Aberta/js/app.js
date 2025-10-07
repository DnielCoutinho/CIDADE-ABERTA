// Configurações globais
const CONFIG = {
    API_BASE_URL: './api',
    API_ENDPOINTS: {
        ocorrencias: './api/ocorrencias_simple.php',
        login: './api/login.php',
        rastreamento: './api/rastreamento.php',
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
    currentUser: null,
    isAuthenticated: false
};

// Inicialização da aplicação
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    try {
        console.log('🚀 Inicializando aplicação...');
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

    // Formulário de login
    const loginForm = document.getElementById('form-login');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLoginSubmit);
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
        const response = await fetch(CONFIG.API_ENDPOINTS.ocorrencias);
        const result = await response.json();
        
        if (result.success && result.data) {
            state.ocorrencias = result.data.map(item => ({
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
            state.ocorrencias.forEach(ocorrencia => {
                const marker = createMapMarker(ocorrencia);
                if (marker) {
                    state.markersLayer.addLayer(marker);
                    state.markers.push({ marker, ocorrencia });
                }
            });
        } else {
            console.warn('Nenhuma ocorrência encontrada ou erro na API');
        }
    } catch (error) {
        console.error('Erro ao carregar ocorrências:', error);
        // Fallback para dados de exemplo em caso de erro na API
        loadSampleOcorrencias();
    }
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
    if (!ocorrencia.coordenadas || ocorrencia.coordenadas.length !== 2) {
        console.warn('Coordenadas inválidas para ocorrência:', ocorrencia);
        return null;
    }

    const [lat, lng] = ocorrencia.coordenadas;
    
    if (isNaN(lat) || isNaN(lng)) {
        console.warn('Coordenadas numéricas inválidas:', lat, lng);
        return null;
    }

    const icon = createMarkerIcon(ocorrencia.status, ocorrencia.tipo);
    
    const marker = L.marker([lat, lng], { icon })
        .bindPopup(createPopupContent(ocorrencia), {
            maxWidth: 300,
            className: 'custom-popup'
        });

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
    
    const codigo = document.getElementById('id-ocorrencia').value;
    const resultadoElement = document.getElementById('resultado-rastreamento');
    
    if (!codigo.trim()) {
        showMessage('error', 'Digite o código da ocorrência');
        return;
    }
    
    try {
        // Mostrar loading
        resultadoElement.innerHTML = '<div class="loading"></div> Buscando informações...';
        resultadoElement.style.display = 'block';
        
        // Buscar na API
        const response = await fetch(`${CONFIG.API_ENDPOINTS.rastreamento}?codigo=${encodeURIComponent(codigo)}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const ocorrencia = result.data;
            updateTrackingResult(ocorrencia);
            
            // Centralizar mapa na ocorrência se existir
            if (ocorrencia.latitude && ocorrencia.longitude) {
                state.map.setView([parseFloat(ocorrencia.latitude), parseFloat(ocorrencia.longitude)], 16);
                
                // Destacar marcador correspondente
                const markerData = state.markers.find(m => m.ocorrencia.codigo === codigo);
                if (markerData) {
                    markerData.marker.openPopup();
                }
            }
        } else {
            resultadoElement.innerHTML = `
                <div class="tracking-result error">
                    <h4>Ocorrência não encontrada</h4>
                    <p>O código "${codigo}" não foi encontrado em nosso sistema.</p>
                    <p>Verifique se o código está correto ou entre em contato conosco.</p>
                </div>
            `;
        }
        
    } catch (error) {
        console.error('Erro ao buscar ocorrência:', error);
        resultadoElement.innerHTML = `
            <div class="tracking-result error">
                <h4>Erro ao buscar informações</h4>
                <p>Houve um problema ao buscar as informações da ocorrência.</p>
                <p>Tente novamente mais tarde.</p>
            </div>
        `;
    }
}

function updateTrackingResult(ocorrencia) {
    const resultadoElement = document.getElementById('resultado-rastreamento');
    
    const statusMap = {
        'pendente': { label: 'Pendente', class: 'pendente' },
        'em_analise': { label: 'Em Análise', class: 'em-analise' },
        'em_andamento': { label: 'Em Andamento', class: 'em-andamento' },
        'concluida': { label: 'Concluída', class: 'concluida' },
        'cancelada': { label: 'Cancelada', class: 'cancelada' }
    };
    
    const statusInfo = statusMap[ocorrencia.status] || { label: ocorrencia.status, class: 'pendente' };
    
    resultadoElement.innerHTML = `
        <div class="tracking-result">
            <h4>Detalhes da Ocorrência</h4>
            <div class="tracking-info">
                <p><strong>Código:</strong> ${ocorrencia.codigo}</p>
                <p><strong>Status:</strong> <span class="status ${statusInfo.class}">${statusInfo.label}</span></p>
                <p><strong>Tipo:</strong> ${ocorrencia.tipo}</p>
                <p><strong>Data:</strong> ${new Date(ocorrencia.data_criacao).toLocaleDateString('pt-BR')}</p>
                <p><strong>Endereço:</strong> ${ocorrencia.endereco}</p>
                <p><strong>Descrição:</strong> ${ocorrencia.descricao}</p>
                <p><strong>Cidadão:</strong> ${ocorrencia.nome_cidadao}</p>
            </div>
            <div class="status-timeline">
                <div class="timeline-item ${['pendente', 'em_analise', 'em_andamento', 'concluida'].includes(ocorrencia.status) ? 'completed' : ''}">
                    <i class="fas fa-check"></i>
                    <span>Ocorrência Registrada</span>
                </div>
                <div class="timeline-item ${['em_analise', 'em_andamento', 'concluida'].includes(ocorrencia.status) ? 'completed' : ''} ${ocorrencia.status === 'em_analise' ? 'active' : ''}">
                    <i class="fas fa-eye"></i>
                    <span>Em Análise</span>
                </div>
                <div class="timeline-item ${['em_andamento', 'concluida'].includes(ocorrencia.status) ? 'completed' : ''} ${ocorrencia.status === 'em_andamento' ? 'active' : ''}">
                    <i class="fas fa-tools"></i>
                    <span>Em Andamento</span>
                </div>
                <div class="timeline-item ${ocorrencia.status === 'concluida' ? 'completed active' : ''}">
                    <i class="fas fa-flag-checkered"></i>
                    <span>Concluída</span>
                </div>
            </div>
        </div>
    `;
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
            
            // Atualizar estatísticas com os dados corretos da API
            updateStatElement('ocorrencias-resolvidas', data.data.ocorrencias_resolvidas || 0);
            updateStatElement('satisfacao', data.data.satisfacao || 0);
            updateStatElement('horas-medias', data.data.horas_medias || 0);
            
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