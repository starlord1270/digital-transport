<?php
/**
 * DIGITAL TRANSPORT - FOOTER COMPONENT (PREMIUM)
 */
?>
    </main>

    <footer style="margin-top: 80px; padding: 40px 0; background: var(--bg-card); border-top: 1px solid rgba(0,0,0,0.05);">
        <div class="container" style="text-align: center;">
            <div class="logo" style="margin-bottom: 20px; align-items: center;">
                Digital Transport
                <span style="font-weight: 400;">Modernizando tu forma de viajar</span>
            </div>
            
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">
                © 2025 Digital Transport - Todos los derechos reservados.
            </p>
            
            <div style="display: flex; justify-content: center; gap: 20px;">
                <a href="#" style="color: var(--text-muted);"><i class="fab fa-facebook"></i></a>
                <a href="#" style="color: var(--text-muted);"><i class="fab fa-twitter"></i></a>
                <a href="#" style="color: var(--text-muted);"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <!-- Scripts Core -->
    <script>
        // Tema Oscuro - Lógica centralizada
        const themeToggle = document.getElementById('theme-toggle');
        const htmlElement = document.documentElement;
        const themeIcon = themeToggle.querySelector('i');
        
        const updateIcon = (theme) => {
            themeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        };

        const savedTheme = localStorage.getItem('theme') || 'light';
        htmlElement.setAttribute('data-theme', savedTheme);
        updateIcon(savedTheme);

        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            htmlElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateIcon(newTheme);
        });
    </script>
</body>
</html>
