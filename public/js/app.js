// Toast Notification System
function showToast(message, type = 'success', duration = 3000) {
    const container = document.createElement('div');
    container.className = 'toast-container';
    const iconMap = { success: '\u2713', error: '\u2715', warning: '\u26A0', info: '\u2139' };
    const toast = document.createElement('div');
    toast.className = 'custom-toast ' + type;
    toast.innerHTML = '<span class="toast-icon">' + (iconMap[type] || '\u2139') + '</span><span class="toast-message">' + message + '</span><button class="toast-close" onclick="this.parentElement.remove()">\u00D7</button>';
    container.appendChild(toast);
    document.body.appendChild(container);
    setTimeout(function() { if (toast.parentElement) { toast.style.opacity = '0'; toast.style.transform = 'translateX(100px)'; toast.style.transition = 'all .3s ease'; setTimeout(function() { toast.remove(); }, 300); } }, duration);
}

// Loading Spinner
function showLoading() { var el = document.createElement('div'); el.className = 'spinner-overlay'; el.innerHTML = '<div class="spinner-border dark-spinner" role="status"></div>'; el.id = 'globalLoading'; document.body.appendChild(el); }
function hideLoading() { var el = document.getElementById('globalLoading'); if (el) el.remove(); }

// Cart Functions
function addToCart(pid, qty, size) { qty = qty || 1; size = size || ''; showLoading(); fetch('ajax/cart.php?action=add', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'product_id=' + pid + '&quantity=' + qty + '&size=' + encodeURIComponent(size) }).then(function(r) { return r.json(); }).then(function(d) { hideLoading(); if (d.success) { showToast('Added to cart!', 'success'); updateCartCount(); } else if (d.error === 'Not logged in') { location.href = 'auth/login.php'; } else { showToast(d.error, 'error'); } }).catch(function(e) { hideLoading(); showToast('Error adding item', 'error'); }); }
function removeFromCart(id) { showLoading(); fetch('ajax/cart.php?action=remove&id=' + id, { method: 'POST' }).then(function(r) { return r.json(); }).then(function(d) { hideLoading(); if (d.success) { showToast('Removed from cart', 'info'); updateCartCount(); } else { showToast('Error removing item', 'error'); } }).catch(function(e) { hideLoading(); showToast('Error', 'error'); }); }
function updateQty(pid, delta) { showLoading(); fetch('ajax/cart.php?action=qty&id=' + pid + '&delta=' + delta, { method: 'POST' }).then(function(r) { return r.json(); }).then(function(d) { hideLoading(); if (d.success) { showToast('Cart updated', 'info'); location.reload(); } else { showToast('Error updating cart', 'error'); } }).catch(function(e) { hideLoading(); showToast('Error', 'error'); }); }
function updateCartCount() { fetch('ajax/cart.php?action=count').then(function(r) { return r.json(); }).then(function(d) { document.querySelectorAll('.cart-count').forEach(function(e) { e.textContent = d.count; e.style.background = '#f0c674'; e.style.color = '#333'; e.style.borderRadius = '50%'; e.style.padding = '2px 8px'; e.style.fontSize = '.75rem'; e.style.fontWeight = '700'; }); }).catch(function() {}); }

// Smooth Scroll
document.querySelectorAll('a[href^="#"]').forEach(function(anchor) { anchor.addEventListener('click', function(e) { var target = document.querySelector(this.getAttribute('href')); if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); } }); });

// Sidebar Toggle for Mobile
(function() { var toggle = document.querySelector('.sidebar-toggle'); if (toggle) { toggle.addEventListener('click', function() { var s = document.querySelector('.sidebar'); if (s) s.classList.toggle('open'); }); } })();

// Panel open/close toggle (admin & staff)
(function() {
    var toggle = document.getElementById('panelToggle');
    var sidebar = document.querySelector('.sidebar');
    if (!toggle || !sidebar) return;
    var icon = toggle.querySelector('i');
    var mq = window.matchMedia('(max-width: 992px)');
    var backdrop = null;

    function isMobile() { return mq.matches; }
    function isCollapsed() { return document.documentElement.classList.contains('panel-collapsed'); }
    function isOpen() { return isMobile() ? sidebar.classList.contains('open') : !isCollapsed(); }
    function setIcon() { if (icon) icon.className = isOpen() ? 'bi bi-x-lg' : 'bi bi-list'; }
    function setAria() { toggle.setAttribute('aria-expanded', isOpen() ? 'true' : 'false'); }

    function removeBackdrop() { if (backdrop) { backdrop.remove(); backdrop = null; } }
    function addBackdrop() {
        if (backdrop) return;
        backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop';
        backdrop.addEventListener('click', function() { sidebar.classList.remove('open'); removeBackdrop(); setIcon(); setAria(); });
        document.body.appendChild(backdrop);
    }

    toggle.addEventListener('click', function() {
        if (isMobile()) {
            if (sidebar.classList.toggle('open')) addBackdrop(); else removeBackdrop();
        } else {
            var collapsed = document.documentElement.classList.toggle('panel-collapsed');
            try { localStorage.setItem('panelCollapsed', collapsed ? '1' : '0'); } catch (e) {}
        }
        setIcon();
        setAria();
    });

    function reset() { sidebar.classList.remove('open'); removeBackdrop(); setIcon(); setAria(); }
    if (mq.addEventListener) mq.addEventListener('change', reset); else if (mq.addListener) mq.addListener(reset);
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') reset(); });

    setIcon();
    setAria();
})();

// Animate elements on scroll
var observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };
var observer = new IntersectionObserver(function(entries) { entries.forEach(function(entry) { if (entry.isIntersecting) { entry.target.classList.add('fade-up'); observer.unobserve(entry.target); } }); }, observerOptions);
document.querySelectorAll('.card, .stat-card, .dashboard-card').forEach(function(el) { observer.observe(el); });

// Add smooth hover effects to all cards
document.addEventListener('DOMContentLoaded', function() { document.querySelectorAll('.product-card, .stat-card, .dashboard-card').forEach(function(card) { card.addEventListener('mouseenter', function() { card.style.transform = 'translateY(-6px)'; }); card.addEventListener('mouseleave', function() { card.style.transform = 'translateY(0)'; }); }); });

// Smooth page transitions
window.addEventListener('load', function() { document.body.style.opacity = '0'; document.body.style.transition = 'opacity .5s ease'; setTimeout(function() { document.body.style.opacity = '1'; }, 100); });
