<?php
/**
 * Teste de Conexão e Funcionalidade
 * Cidade Aberta Santarém
 */

require_once 'database/Connection.php';

echo "<h1>🧪 Teste de Conexão - Cidade Aberta</h1>";

try {
    // Testar conexão
    echo "<h2>1. 🔌 Teste de Conexão</h2>";
    $db = Database::getInstance();
    
    if ($db->testConnection()) {
        echo "<p style='color: green'>✅ Conexão com banco OK!</p>";
    } else {
        echo "<p style='color: red'>❌ Falha na conexão!</p>";
        exit;
    }
    
    // Testar tabelas
    echo "<h2>2. 📊 Verificação de Tabelas</h2>";
    $connection = $db->getConnection();
    
    $tables = ['ocorrencias', 'contatos', 'gestores'];
    foreach ($tables as $table) {
        try {
            $stmt = $connection->query("SELECT COUNT(*) as total FROM {$table}");
            $count = $stmt->fetch()['total'];
            echo "<p style='color: green'>✅ Tabela {$table}: {$count} registros</p>";
        } catch (Exception $e) {
            echo "<p style='color: red'>❌ Tabela {$table}: Não encontrada</p>";
        }
    }
    
    // Testar API de ocorrências
    echo "<h2>3. 🔧 Teste de APIs</h2>";
    echo "<p><a href='api/ocorrencias_simple.php' target='_blank' style='background: blue; color: white; padding: 10px; text-decoration: none;'>🔗 Testar API Ocorrências</a></p>";
    echo "<p><a href='api/contato_simple.php' target='_blank' style='background: green; color: white; padding: 10px; text-decoration: none;'>🔗 Testar API Contato</a></p>";
    echo "<p><a href='api/stats.php' target='_blank' style='background: orange; color: white; padding: 10px; text-decoration: none;'>🔗 Testar API Estatísticas</a></p>";
    
    // Formulário de teste rápido para ocorrência
    echo "<h2>4. 📝 Teste Rápido de Formulário</h2>";
    ?>
    
    <form id="testeForm" style="border: 1px solid #ccc; padding: 20px; max-width: 500px;">
        <h3>Teste de Ocorrência</h3>
        <p>
            <label>Tipo:</label><br>
            <select name="tipo" required>
                <option value="buraco">Buraco</option>
                <option value="lixo">Lixo</option>
                <option value="iluminacao">Iluminação</option>
            </select>
        </p>
        <p>
            <label>Endereço:</label><br>
            <input type="text" name="endereco" value="Rua Teste, Centro" required style="width: 100%;">
        </p>
        <p>
            <label>Descrição:</label><br>
            <textarea name="descricao" required style="width: 100%;">Teste de ocorrência via formulário</textarea>
        </p>
        <p>
            <button type="submit" style="background: blue; color: white; padding: 10px 20px; border: none;">Enviar Teste</button>
        </p>
        <div id="resultado" style="margin-top: 20px;"></div>
    </form>
    
    <script>
    document.getElementById('testeForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        
        document.getElementById('resultado').innerHTML = '<p>🔄 Enviando...</p>';
        
        try {
            const response = await fetch('api/ocorrencias_simple.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                document.getElementById('resultado').innerHTML = 
                    '<p style="color: green">✅ Sucesso: ' + result.message + 
                    '<br>Código: ' + result.data.codigo + '</p>';
            } else {
                document.getElementById('resultado').innerHTML = 
                    '<p style="color: red">❌ Erro: ' + result.message + '</p>';
            }
        } catch (error) {
            document.getElementById('resultado').innerHTML = 
                '<p style="color: red">❌ Erro de conexão: ' + error.message + '</p>';
        }
    });
    </script>
    
    <?php
    
} catch (Exception $e) {
    echo "<p style='color: red'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>