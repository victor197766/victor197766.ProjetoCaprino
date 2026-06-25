document.addEventListener('DOMContentLoaded', () => {
    const dropdown = document.getElementById('notificationModal');
    const notifBtnDesktop = document.getElementById('notificationBtnDesktop');
    const notifBtnMobile = document.getElementById('notificationBtn');
    const notifBtns = document.querySelectorAll('.notification-btn');

    function positionDropdown(btn) {
        if (!dropdown || !btn) return;
        const rect = btn.getBoundingClientRect();
        dropdown.style.position = 'fixed';
        dropdown.style.top = (rect.bottom + 8) + 'px';

        // Position to the right edge of the button
        const rightOffset = window.innerWidth - rect.right;
        dropdown.style.right = Math.max(8, rightOffset) + 'px';
        dropdown.style.left = 'auto';
    }

    function toggleDropdown(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        if (!dropdown) return;

        const isOpen = dropdown.classList.contains('active');
        if (isOpen) {
            closeDropdown();
        } else {
            positionDropdown(e.currentTarget);
            dropdown.classList.add('active');
        }
    }

    function closeDropdown() {
        if (dropdown) {
            dropdown.classList.remove('active');
        }
    }

    // Attach click handlers to all notification buttons
    if (notifBtnDesktop) {
        notifBtnDesktop.addEventListener('click', toggleDropdown);
    }
    if (notifBtnMobile) {
        notifBtnMobile.addEventListener('click', toggleDropdown);
    }
    notifBtns.forEach(btn => {
        if (btn !== notifBtnDesktop && btn !== notifBtnMobile) {
            btn.addEventListener('click', toggleDropdown);
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (dropdown && dropdown.classList.contains('active')) {
            if (!dropdown.contains(e.target) && !e.target.closest('.notification-btn')) {
                closeDropdown();
            }
        }
    });

    // Close dropdown on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeDropdown();
        }
    });

    // Reposition on resize
    window.addEventListener('resize', () => {
        if (dropdown && dropdown.classList.contains('active')) {
            closeDropdown();
        }
    });
});
