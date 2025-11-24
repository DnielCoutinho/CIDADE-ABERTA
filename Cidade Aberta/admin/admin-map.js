
document.addEventListener('DOMContentLoaded', function() {
    // Mapa para Administradores
    const map = L.map('leaflet-map-admin').setView([-2.43, -54.72], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    async function carregarOcorrenciasNoMapa() {
        try {
            const response = await fetch('../api/ocorrencias.php');
            const result = await response.json();

            if (result.success) {
                const ocorrencias = result.data.ocorrencias || [];
                ocorrencias.forEach(ocorrencia => {
                    if (ocorrencia.latitude && ocorrencia.longitude) {
                        const marker = L.marker([ocorrencia.latitude, ocorrencia.longitude]).addTo(map);
                        marker.bindPopup(`
                            <b>${ocorrencia.tipo}</b><br>
                            ${ocorrencia.endereco}<br>
                            Status: ${ocorrencia.status}
                        `);
                    }
                });
            }
        } catch (error) {
            console.error('Erro ao carregar ocorrências no mapa:', error);
        }
    }

    carregarOcorrenciasNoMapa();
});
