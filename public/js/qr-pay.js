(function () {
    'use strict';

    var autoTimer = null;
    var pollTimer = null;
    var closeHook = null;

    function ready() {
        return typeof window.QRCode !== 'undefined';
    }

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
        });
    }

    function payload(o) {
        return [
            'COFFEE POS - QR PAYMENT',
            'Store: ' + (o.store || ''),
            'Reference: ' + (o.ref || ''),
            'Amount: ' + (o.amountText || ''),
            'Currency: ' + (o.currency || ''),
            'Scan this code to pay'
        ].join('\n');
    }

    function render(host, text, size) {
        if (!host) return;
        host.innerHTML = '';
        if (!ready()) {
            host.innerHTML = '<div style="color:#c0392b;font-size:12px;padding:10px">QR library could not be loaded.</div>';
            return;
        }
        try {
            new window.QRCode(host, {
                text: text,
                width: size || 160,
                height: size || 160,
                colorDark: '#22150F',
                colorLight: '#ffffff',
                correctLevel: window.QRCode.CorrectLevel.M
            });
        } catch (e) {
            host.innerHTML = '<div style="color:#c0392b;font-size:12px;padding:10px">Could not render the QR code.</div>';
        }
    }

    function newRef() {
        return 'QR-' + Date.now().toString(36).toUpperCase() + Math.floor(10 + Math.random() * 90);
    }

    function isoFor(currency) {
        var map = {
            USD: '840', KHR: '116', EUR: '978', GBP: '829', JPY: '392', CNY: '156',
            THB: '764', VND: '704', KRW: '410', MYR: '458', SGD: '702', AUD: '036',
            CAD: '124', INR: '356', IDR: '360', PHP: '608'
        };
        return map[String(currency || '').trim().toUpperCase()] || '';
    }

    function crc16(str) {
        var crc = 0xFFFF;
        for (var i = 0; i < str.length; i++) {
            crc ^= str.charCodeAt(i) << 8;
            for (var b = 0; b < 8; b++) {
                crc = (crc & 0x8000) ? ((crc << 1) ^ 0x1021) : (crc << 1);
                crc &= 0xFFFF;
            }
        }
        return ('0000' + crc.toString(16).toUpperCase()).slice(-4);
    }

    function withAmount(payload, amount, currency) {
        payload = String(payload || '').trim();
        amount = Number(amount);
        if (!payload || payload.indexOf('000201') !== 0 || !isFinite(amount) || amount <= 0) return payload;

        var fields = [];
        var i = 0;
        while (i + 4 <= payload.length) {
            var tag = payload.substr(i, 2);
            var len = parseInt(payload.substr(i + 2, 2), 10);
            if (!/^\d{2}$/.test(tag) || isNaN(len) || len > 99) { fields = null; break; }
            var val = payload.substr(i + 4, len);
            if (val.length !== len) { fields = null; break; }
            fields.push([tag, val]);
            i += 4 + len;
        }
        if (!fields || !fields.length) return payload;

        var iso = isoFor(currency);
        var amountStr = amount.toFixed(2);
        var out = [];
        var inserted = false;
        for (var k = 0; k < fields.length; k++) {
            var t = fields[k][0], v = fields[k][1];
            if (t === '63' || t === '54') continue;
            if (t === '53' && iso) v = iso;
            out.push([t, v]);
            if (t === '53' && !inserted) { out.push(['54', amountStr]); inserted = true; }
        }
        if (!inserted) {
            var at = out.length;
            for (var j = 0; j < out.length; j++) {
                if (['55', '56', '57', '58'].indexOf(out[j][0]) !== -1) { at = j; break; }
            }
            out.splice(at, 0, ['54', amountStr]);
        }

        var s = '';
        for (var n = 0; n < out.length; n++) {
            s += out[n][0] + ('00' + out[n][1].length).slice(-2) + out[n][1];
        }
        s += '6304';
        return s + crc16(s);
    }

    var CSS = [
        '.qrp-overlay{position:fixed;inset:0;background:rgba(24,15,11,.74);z-index:3000;display:flex;align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(3px);animation:qrpFade .18s ease}',
        '@keyframes qrpFade{from{opacity:0}to{opacity:1}}',
        '.qrp-modal{background:#fff;border-radius:18px;width:100%;max-width:390px;max-height:94vh;overflow:auto;box-shadow:0 24px 60px rgba(0,0,0,.4);animation:qrpPop .2s ease}',
        '@keyframes qrpPop{from{opacity:0;transform:scale(.94) translateY(10px)}to{opacity:1;transform:none}}',
        '.qrp-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 16px;background:linear-gradient(135deg,#3E2723,#6F4E37);color:#fff;font-weight:800;font-size:.95rem;position:sticky;top:0;z-index:2}',
        '.qrp-x{background:rgba(255,255,255,.18);border:none;color:#fff;width:30px;height:30px;border-radius:50%;font-size:1.25rem;line-height:1;cursor:pointer;flex:0 0 auto}',
        '.qrp-x:hover{background:rgba(255,255,255,.34)}',
        '.qrp-body{padding:18px 16px 4px;text-align:center}',
        '.qrp-main{display:flex;justify-content:center;min-height:232px}',
        '.qrp-main img{width:232px;height:232px;object-fit:contain;border:1px solid #e8e0d8;border-radius:12px;background:#fff}',
        '.qrp-label{font-size:.68rem;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:#A08D80;margin-top:12px}',
        '.qrp-amount{font-size:2rem;font-weight:800;color:#5D4037;line-height:1.2}',
        '.qrp-cur{font-size:.74rem;color:#A08D80;font-weight:700;letter-spacing:.05em}',
        '.qrp-ref{font-size:.76rem;color:#888;margin-top:6px}',
        '.qrp-hint{font-size:.74rem;color:#6f6f6f;margin-top:12px}',
        '.qrp-step{display:inline-block;font-size:.68rem;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:#1E7A4F;background:#EAF7F0;border:1px solid #CDEBDC;padding:5px 11px;border-radius:999px;margin-bottom:10px}',
        '.qrp-auto{margin-top:12px}',
        '.qrp-bar{height:8px;border-radius:999px;background:#EFE7E0;overflow:hidden}',
        '.qrp-bar i{display:block;height:100%;width:0;background:linear-gradient(90deg,#2E9E63,#14603B);transition:width .1s linear}',
        '.qrp-bar.indet i{width:45%;animation:qrpIndet 1.3s ease-in-out infinite}',
        '@keyframes qrpIndet{0%{transform:translateX(-110%)}100%{transform:translateX(240%)}}',
        '.qrp-auto-txt{font-size:.75rem;font-weight:800;color:#1E7A4F;margin-top:7px;letter-spacing:.03em}',
        '.qrp-auto-txt.err{color:#C0392B}',
        '.qrp-foot{display:flex;gap:10px;padding:14px 16px 16px}',
        '.qrp-btn{flex:1;border:none;border-radius:12px;padding:12px 10px;font-size:.9rem;font-weight:800;cursor:pointer;transition:.2s}',
        '.qrp-btn-ghost{background:#F1EAE3;color:#5D4037}',
        '.qrp-btn-ghost:hover{background:#E6DBD1}',
        '.qrp-btn-primary{background:linear-gradient(135deg,#1E7A4F,#14603B);color:#fff;box-shadow:0 6px 16px rgba(30,122,79,.32)}',
        '.qrs-overlay{position:fixed;inset:0;z-index:4000;background:linear-gradient(160deg,#12603B,#0E3B28);display:flex;align-items:center;justify-content:center;padding:20px;animation:qrsFade .25s ease}',
        '@keyframes qrsFade{from{opacity:0}to{opacity:1}}',
        '.qrs-card{text-align:center;color:#fff;max-width:360px;animation:qrsPop .45s cubic-bezier(.2,1.2,.4,1)}',
        '@keyframes qrsPop{from{opacity:0;transform:scale(.75)}to{opacity:1;transform:none}}',
        '.qrs-svg{width:118px;height:118px}',
        '.qrs-circle{fill:none;stroke:#5CE39A;stroke-width:4;stroke-linecap:round;stroke-dasharray:183;stroke-dashoffset:183;animation:qrsDraw .6s ease forwards}',
        '.qrs-tick{fill:none;stroke:#5CE39A;stroke-width:5;stroke-linecap:round;stroke-linejoin:round;stroke-dasharray:50;stroke-dashoffset:50;animation:qrsDraw .45s .4s ease forwards}',
        '@keyframes qrsDraw{to{stroke-dashoffset:0}}',
        '.qrs-title{font-size:1.3rem;font-weight:900;letter-spacing:.07em;margin-top:16px}',
        '.qrs-amount{font-size:2.7rem;font-weight:900;margin-top:8px;line-height:1.1}',
        '.qrs-cur{font-size:.78rem;opacity:.78;letter-spacing:.16em;font-weight:800;margin-top:2px}',
        '.qrs-meta{font-size:.85rem;opacity:.92;margin-top:14px;line-height:1.7}',
        '.qrs-sub{font-size:.8rem;opacity:.7;margin-top:10px}'
    ].join('');

    function ensureStyles() {
        if (document.getElementById('qrpStyles')) return;
        var st = document.createElement('style');
        st.id = 'qrpStyles';
        st.textContent = CSS;
        document.head.appendChild(st);
    }

    function stopAuto() {
        if (autoTimer) { clearInterval(autoTimer); autoTimer = null; }
    }

    function stopPoll() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    function onEsc(e) {
        if (e.key === 'Escape') closeScan();
    }

    function closeScan() {
        stopAuto();
        stopPoll();
        var el = document.getElementById('qrpOverlay');
        var had = !!el;
        if (el && el.parentNode) el.parentNode.removeChild(el);
        document.removeEventListener('keydown', onEsc);
        var hook = closeHook;
        closeHook = null;
        if (had && typeof hook === 'function') hook();
    }

    function openScan(o) {
        closeScan();
        ensureStyles();

        var bankQr = o.bankQr || o.secondaryQr || '';
        var bankPayload = o.bankQrPayload || '';
        var hasBank = !!bankQr || !!bankPayload;
        var hasLink = !!o.payload;

        var autoMs = o.autoMs == null ? 0 : Number(o.autoMs);
        var hasPoll = typeof o.statusFn === 'function';
        var showStatus = hasPoll || autoMs > 0;
        var pendingText = o.pendingText || 'Waiting for payment confirmation...';
        var fired = false;

        var el = document.createElement('div');
        el.className = 'qrp-overlay';
        el.id = 'qrpOverlay';
        el.innerHTML =
            '<div class="qrp-modal" role="dialog" aria-modal="true" aria-label="Scan QR to pay">' +
                '<div class="qrp-head"><span>Scan QR to pay</span>' +
                '<button type="button" class="qrp-x" data-qrp="close" aria-label="Cancel">&times;</button></div>' +
                '<div class="qrp-body">' +
                    (hasBank ? '<div class="qrp-step">Pay with your banking app</div>' : '') +
                    '<div class="qrp-main" id="qrpMain"></div>' +
                    '<div class="qrp-label">Amount to pay</div>' +
                    '<div class="qrp-amount" id="qrpAmount"></div>' +
                    '<div class="qrp-cur" id="qrpCur"></div>' +
                    '<div class="qrp-ref" id="qrpRef"></div>' +
                    '<div class="qrp-hint" id="qrpHint">Show this code to the customer.</div>' +
                    (showStatus
                        ? '<div class="qrp-auto"><div class="qrp-bar' + (hasPoll && autoMs <= 0 ? ' indet' : '') + '"><i id="qrpBarFill"></i></div>' +
                          '<div class="qrp-auto-txt" id="qrpAutoTxt">' + esc(hasPoll ? pendingText : 'Waiting for payment...') + '</div></div>'
                        : '') +
                '</div>' +
                '<div class="qrp-foot">' +
                    '<button type="button" class="qrp-btn qrp-btn-ghost" data-qrp="close">Cancel</button>' +
                    '<button type="button" class="qrp-btn qrp-btn-primary" data-qrp="done">' + esc(o.doneLabel || 'Payment received') + '</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(el);
        closeHook = (typeof o.onClose === 'function') ? o.onClose : null;

        var main = el.querySelector('#qrpMain');
        var generated = '';
        if (bankPayload && Number(o.amount) > 0) {
            generated = withAmount(bankPayload, o.amount, o.bankQrCurrency || o.currency);
        }
        if (generated) {
            render(main, generated, o.mainSize || 232);
        } else if (bankQr) {
            main.innerHTML = '<img src="' + esc(bankQr) + '" alt="Bank QR">';
        } else if (bankPayload) {
            render(main, bankPayload, o.mainSize || 232);
        } else if (hasLink) {
            render(main, o.payload, o.mainSize || 232);
        }
        el.querySelector('#qrpAmount').textContent = o.amountText || '';
        el.querySelector('#qrpCur').textContent = o.currency || '';
        el.querySelector('#qrpRef').textContent = o.ref ? 'Ref: ' + o.ref : '';
        if (o.hint) el.querySelector('#qrpHint').textContent = o.hint;

        function setTxt(msg, isErr) {
            var t = el.querySelector('#qrpAutoTxt');
            if (!t) return;
            t.textContent = msg;
            t.className = 'qrp-auto-txt' + (isErr ? ' err' : '');
        }

        function fire() {
            if (fired) return;
            fired = true;
            stopAuto();
            stopPoll();
            var keep = false;
            if (typeof o.onDone === 'function') keep = (o.onDone() === false);
            if (!keep) { closeHook = null; closeScan(); }
        }

        if (autoMs > 0) {
            var fill = el.querySelector('#qrpBarFill');
            var started = Date.now();
            autoTimer = setInterval(function () {
                var elapsed = Date.now() - started;
                var p = Math.min(1, elapsed / autoMs);
                if (fill) fill.style.width = (p * 100) + '%';
                setTxt(p >= 1 ? 'Payment received' : 'Confirming payment... ' + Math.ceil((autoMs - elapsed) / 1000) + 's');
                if (p >= 1) fire();
            }, 100);
        }

        if (hasPoll) {
            var inFlight = false;
            var poll = function () {
                if (inFlight || fired) return;
                inFlight = true;
                var p;
                try { p = Promise.resolve(o.statusFn()); } catch (e) { inFlight = false; return; }
                p.then(function (res) {
                    inFlight = false;
                    if (fired || document.getElementById('qrpOverlay') !== el) return;
                    var st = res && res.state;
                    if (st === 'completed' || st === 'paid' || st === 'success') { setTxt('Payment received'); fire(); return; }
                    if (st === 'pending') { setTxt(pendingText); return; }
                    stopPoll();
                    if (st === 'cancelled') setTxt('Payment cancelled. Cancel and start again.', true);
                    else if (st === 'expired') setTxt('This payment link expired. Cancel and start again.', true);
                    else setTxt('Payment session not found. Cancel and start again.', true);
                }, function () { inFlight = false; });
            };
            poll();
            pollTimer = setInterval(poll, o.pollMs || 1500);
        }

        el.addEventListener('click', function (e) {
            if (e.target === el) { closeScan(); return; }
            var t = e.target.closest ? e.target.closest('[data-qrp]') : null;
            if (!t) return;
            var act = t.getAttribute('data-qrp');
            if (act === 'close') { closeScan(); return; }
            if (act === 'done') {
                if (typeof o.confirmFn !== 'function') { fire(); return; }
                if (t.disabled) return;
                t.disabled = true;
                var label = t.innerHTML;
                t.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
                var p;
                try { p = Promise.resolve(o.confirmFn()); } catch (err) { p = Promise.reject(err); }
                p.then(function () {
                    fire();
                }, function (err) {
                    t.disabled = false;
                    t.innerHTML = label;
                    var msg = (err && err.message) ? err.message : 'Could not confirm the payment. Try again.';
                    var txt = el.querySelector('#qrpAutoTxt');
                    if (txt) setTxt(msg, true);
                    else { t.innerHTML = esc(msg); }
                });
            }
        });
        document.addEventListener('keydown', onEsc);
    }

    function successScreen(o) {
        ensureStyles();
        var old = document.getElementById('qrsOverlay');
        if (old && old.parentNode) old.parentNode.removeChild(old);

        var el = document.createElement('div');
        el.className = 'qrs-overlay';
        el.id = 'qrsOverlay';
        el.innerHTML =
            '<div class="qrs-card">' +
                '<svg class="qrs-svg" viewBox="0 0 64 64" aria-hidden="true">' +
                    '<circle class="qrs-circle" cx="32" cy="32" r="29"/>' +
                    '<path class="qrs-tick" d="M18 33l10 10 19-21"/>' +
                '</svg>' +
                '<div class="qrs-title">PAYMENT SUCCESSFUL</div>' +
                '<div class="qrs-amount">' + esc(o.amountText || '') + '</div>' +
                '<div class="qrs-cur">' + esc(o.currency || '') + '</div>' +
                '<div class="qrs-meta">' +
                    (o.order ? '<div>Order: ' + esc(o.order) + '</div>' : '') +
                    (o.ref ? '<div>Reference: ' + esc(o.ref) + '</div>' : '') +
                '</div>' +
                '<div class="qrs-sub">' + esc(o.message || 'Thank you!') + '</div>' +
            '</div>';
        document.body.appendChild(el);

        var life = o.duration || 3500;
        setTimeout(function () {
            if (typeof o.onEnd === 'function') o.onEnd();
            setTimeout(function () {
                if (el.parentNode) el.parentNode.removeChild(el);
            }, 700);
        }, life);
        return el;
    }

    window.QrPay = {
        payload: payload,
        render: render,
        newRef: newRef,
        withAmount: withAmount,
        isoFor: isoFor,
        crc16: crc16,
        openScan: openScan,
        closeScan: closeScan,
        successScreen: successScreen
    };
})();
