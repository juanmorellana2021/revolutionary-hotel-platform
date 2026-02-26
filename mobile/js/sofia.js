/**
 * AiNi Travel Mobile — Sofia AI Chat
 */

function openSofia() {
    new bootstrap.Offcanvas(document.getElementById('sofiaChat')).show();
}

async function sendSofia() {
    const input = document.getElementById('sofiaInput');
    const msg   = input.value.trim();
    if (!msg) return;

    input.value = '';
    appendSofiaMsg(msg, 'user');

    // Typing indicator
    const typingId = 'typing_' + Date.now();
    appendSofiaMsg('Escribiendo...', 'bot', typingId);

    try {
        const res = await fetch('/sofia_travel_api_simple.php?action=chat', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ message: msg })
        });
        const data = await res.json();

        // Remove typing indicator
        const typingEl = document.getElementById(typingId);
        if (typingEl) typingEl.remove();

        if (data.response) {
            appendSofiaMsg(data.response, 'bot');
        } else {
            appendSofiaMsg('Lo siento, intenta de nuevo.', 'bot');
        }
    } catch (e) {
        const typingEl = document.getElementById(typingId);
        if (typingEl) typingEl.remove();
        appendSofiaMsg('Sin conexión. Verifica tu red.', 'bot');
    }
}

function appendSofiaMsg(text, type, id) {
    const container = document.getElementById('sofiaMessages');
    const div = document.createElement('div');
    div.className = `aini-sofia-msg ${type}`;
    if (id) div.id = id;
    div.innerHTML = `<div class="aini-sofia-bubble">${text}</div>`;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
}

// Send on Enter key
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('sofiaInput');
    if (input) {
        input.addEventListener('keypress', e => {
            if (e.key === 'Enter') sendSofia();
        });
    }
});
