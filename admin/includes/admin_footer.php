        </main> <!-- End admin-content -->
    </div> <!-- End admin-main -->
</div> <!-- End admin-wrapper -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('adminSidebar');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
            }
        });
    }
});
</script>
</body>
</html>
