        </main>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script>
    function toggleUserMenu() {
        document.getElementById('userDropdown').classList.toggle('show');
    }
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('userDropdown');
        const avatar = document.querySelector('.user-avatar');
        if (menu && avatar && !avatar.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.remove('show');
        }
    });
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = 'toast ' + type;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
    </script>
</body>
</html>
