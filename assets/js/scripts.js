// assets/js/scripts.js

$(document).ready(function() {
    // Exemplo: Fade in do formulário quando a página carrega
    $("#formChamado").hide().fadeIn(1000);

    // Outras animações podem ser adicionadas aqui conforme necessário
    // Exemplo: Animação ao submeter o formulário
    $("#formChamado").on('submit', function(e) {
        $(this).find('button[type="submit"]').prop('disabled', true)
            .html('Enviando...');
    });
});
