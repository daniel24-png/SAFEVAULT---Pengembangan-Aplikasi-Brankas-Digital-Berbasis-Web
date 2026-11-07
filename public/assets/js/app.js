/**
 * SAFEVAULT - Client-Side Scripts (v0.1)
 */
document.addEventListener('DOMContentLoaded', function () {
    // Tangani konfirmasi submit form logout tanpa inline event handler
    const logoutButtons = document.querySelectorAll('.btn-logout');
    logoutButtons.forEach(function (button) {
        button.addEventListener('click', function (e) {
            if (!confirm('Apakah Anda yakin ingin keluar?')) {
                e.preventDefault();
            }
        });
    });
});

