<?php
/**
 * SERSOLTEC - ADMIN FOOTER
 * Footer dla panelu administratora
 */
?>

        </div> <!-- .admin-content -->
    </main> <!-- .admin-main -->
</div> <!-- .admin-wrapper -->

<script>
// Mobile menu toggle
document.getElementById('mobileMenuToggle')?.addEventListener('click', function() {
    document.querySelector('.admin-sidebar').classList.toggle('active');
});

// Close sidebar on link click (mobile)
document.querySelectorAll('.admin-nav-item').forEach(link => {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            document.querySelector('.admin-sidebar').classList.remove('active');
        }
    });
});

// Confirm delete actions
document.querySelectorAll('.btn-delete, .delete-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (!confirm('Czy na pewno chcesz usunąć ten element?')) {
            e.preventDefault();
            return false;
        }
    });
});

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    document.querySelectorAll('.alert').forEach(alert => {
        alert.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => alert.remove(), 300);
    });
}, 5000);
</script>

</body>
</html>
