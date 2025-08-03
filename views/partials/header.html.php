<header class="w-full p-4 bg-white shadow-md">
    <div class="container mx-auto flex items-center justify-between">
        <!-- Logo -->
        <div class="text-xl font-bold text-blue-600">
            MathLab
        </div>

        <!-- Navigation (Desktop) -->
        <nav class="hidden md:flex gap-6">
            <a href="/" class="text-gray-700 hover:text-blue-600">Home</a>
            <a href="/calculator" class="text-gray-700 hover:text-blue-600">Calculator</a>
            <a href="/about" class="text-gray-700 hover:text-blue-600">About</a>
            <a href="/contact" class="text-gray-700 hover:text-blue-600">Contact</a>
        </nav>

        <!-- Mobile Menu Button -->
        <button id="menu-toggle" class="md:hidden text-gray-700 focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor"
                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
    </div>

    <!-- Mobile Nav (hidden by default) -->
    <nav id="mobile-menu" class="md:hidden hidden px-4 pt-2 pb-4 bg-white border-t border-gray-200">
        <a href="/" class="block py-2 text-gray-700 hover:text-blue-600">Home</a>
        <a href="/calculator" class="block py-2 text-gray-700 hover:text-blue-600">Calculator</a>
        <a href="/about" class="block py-2 text-gray-700 hover:text-blue-600">About</a>
        <a href="/contact" class="block py-2 text-gray-700 hover:text-blue-600">Contact</a>
    </nav>

    <script>
        // Simple toggle script
        document.getElementById('menu-toggle')?.addEventListener('click', () => {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
    </script>
</header>
