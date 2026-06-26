 
function deleteRule(ruleId) {
    if (confirm('Bu bildirim kuralını silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz!')) {
        // Butonu devre dışı bırak
        const button = event.target.closest('button');
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch(base_url+'pages/ajax/delete_notification_rule.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ rule_id: ruleId })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Başarı mesajı göster
                alert('Bildirim kuralı başarıyla silindi!');
                location.reload();
            } else {
                alert('Hata: ' + data.message);
                // Butonu eski haline getir
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-trash"></i>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu: ' + error.message);
            // Butonu eski haline getir
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-trash"></i>';
        });
    }
}
 