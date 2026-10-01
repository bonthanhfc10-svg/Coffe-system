<?php
require_once __DIR__ . '/../includes/functions.php';
requireStaff();
$pdo = getPDO();
$products = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1 ORDER BY p.name")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll();
$cart = $_SESSION['pos_cart'] ?? [];
$taxRate = (float)setting('tax_rate', 0);
$baseUrl = BASE_URL;
$moneySym = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : ((defined('CURRENCY') && CURRENCY === 'USD') ? '$' : '៛');

$allowCard = settingBool('allow_card_payment', false);
$allowQR = settingBool('allow_qr_payment', true);
$paymentVisible = ['cash' => true, 'card' => $allowCard, 'qr' => $allowQR];
$qrImageSrc = paymentQrImage();
$qrPayload = paymentQrPayload();
$paymentOptions = [
    'cash' => ['bi-cash', 'Cash'],
    'card' => ['bi-credit-card', 'Card'],
    'qr'   => ['bi-qr-code',  'QR'],
];
$activePayment = setting('default_payment_method', 'cash');
if ($activePayment === 'online') $activePayment = 'qr';
if (!isset($paymentVisible[$activePayment]) || !$paymentVisible[$activePayment]) $activePayment = 'cash';
$defaultDiscount = (float)setting('default_discount', 0);
if ($defaultDiscount < 0) $defaultDiscount = 0;
if ($defaultDiscount > 100) $defaultDiscount = 100;

$posProducts = [];
$total = 0;
$cartCount = 0;
foreach ($products as $p) {
    $hasImage = !empty($p['image']) && file_exists(__DIR__ . '/../public/uploads/products/' . $p['image']);
    $posProducts[$p['id']] = [
        'name'   => $p['name'],
        'cat'    => $p['category_name'],
        'price'  => (float)$p['price'],
        'stock'  => (int)$p['stock_quantity'],
        'img'    => $hasImage ? $baseUrl . '/public/uploads/products/' . $p['image'] : '',
    ];
}
foreach ($cart as $id => $item) {
    $qty = is_array($item) ? (int)$item['qty'] : (int)$item;
    if (!isset($posProducts[$id])) continue;
    $cartCount += $qty;
    $total += $posProducts[$id]['price'] * $qty;
}
$taxAmt = $total * ($taxRate / 100);
$grandTotal = $total + $taxAmt;
$taxLabel = rtrim(rtrim(number_format($taxRate, 2, '.', ''), '0'), '.');
if ($taxLabel === '') $taxLabel = '0';
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>POS - <?php echo htmlspecialchars(siteName()); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?php echo $baseUrl; ?>/public/css/style.css?v=3" rel="stylesheet">
<script src="<?php echo $baseUrl; ?>/public/js/qrcode.min.js"></script>
<script src="<?php echo $baseUrl; ?>/public/js/qr-pay.js"></script>
<style>
body.pos-page{background:#FBF7F2;overflow:hidden}
#siteFooter{display:none!important}

.pos-layout{margin-left:260px;padding-top:56px;height:100vh;overflow:hidden;display:flex;flex-direction:column}
.pos-shell{flex:1;display:flex;min-height:0;overflow:hidden}

.pos-catalog{flex:1;display:flex;flex-direction:column;min-width:0;background:#FBF7F2}

.pos-topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 22px;background:#fff;border-bottom:1px solid #EFE5DA}
.pos-topbar-title{display:flex;align-items:center;gap:12px}
.pos-topbar-title .ico{width:38px;height:38px;border-radius:12px;background:linear-gradient(135deg,#5D4037,#8D6E63);color:#fff;display:grid;place-items:center;font-size:1.05rem;box-shadow:0 6px 14px rgba(93,64,55,.28)}
.pos-topbar h1{margin:0;font-size:1.12rem;font-weight:800;color:#3E2723;line-height:1.2}
.pos-topbar .sub{font-size:.74rem;color:#A08D80;font-weight:600}
.pos-topbar-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.pos-pill{display:flex;align-items:center;gap:7px;background:#FBF3EA;border:1px solid #EEE0D2;padding:7px 14px;border-radius:999px;font-weight:800;font-size:.8rem;color:#5D4037}
.pos-pill.dark{background:linear-gradient(135deg,#5D4037,#3E2723);border-color:#3E2723;color:#F3E3D3}

.pos-toolbar{background:#fff;border-bottom:1px solid #EFE5DA;padding:12px 22px;display:flex;gap:14px;align-items:center;flex-wrap:wrap}
.pos-search{position:relative;flex:1;min-width:200px;max-width:430px}
.pos-search input{width:100%;border:2px solid #EEE0D2;background:#FBF7F2;border-radius:12px;padding:9px 14px 9px 40px;font-size:.9rem;color:#33241E;outline:none;transition:.2s}
.pos-search input:focus{border-color:#8D6E63;background:#fff;box-shadow:0 0 0 4px rgba(141,110,99,.12)}
.pos-search i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#B9A99C;font-size:.95rem}
.pos-chips{display:flex;gap:8px;overflow-x:auto;padding-bottom:2px;scrollbar-width:thin}
.pos-chips::-webkit-scrollbar{height:5px}
.pos-chips::-webkit-scrollbar-thumb{background:#E0D2C4;border-radius:5px}
.pos-chip{border:2px solid #EEE0D2;background:#fff;padding:7px 16px;border-radius:999px;font-size:.79rem;font-weight:700;color:#6D5B51;cursor:pointer;white-space:nowrap;transition:.2s}
.pos-chip:hover{border-color:#C9B6A8;color:#5D4037}
.pos-chip.active{background:#5D4037;border-color:#5D4037;color:#fff;box-shadow:0 5px 14px rgba(93,64,55,.3)}

.pos-scroll{flex:1;min-height:0;overflow-y:auto;padding:18px 22px 28px}
.pos-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:16px}
.pos-count{font-size:.76rem;color:#A08D80;font-weight:600;margin:0 0 12px}

.pos-pcard{background:#fff;border:2px solid transparent;border-radius:16px;overflow:hidden;cursor:pointer;display:flex;flex-direction:column;transition:.25s;position:relative;box-shadow:0 2px 10px rgba(62,39,35,.07)}
.pos-pcard:hover{border-color:#8D6E63;transform:translateY(-4px);box-shadow:0 14px 28px rgba(62,39,35,.16)}
.pos-pcard.out{opacity:.62;cursor:not-allowed}
.pos-pcard.out:hover{border-color:transparent;transform:none;box-shadow:0 2px 10px rgba(62,39,35,.07)}
.pos-pmedia{position:relative;height:130px;background:linear-gradient(135deg,#EFE0D2,#DCC7B4);overflow:hidden}
.pos-pmedia img{width:100%;height:100%;object-fit:cover;display:block;transition:.45s}
.pos-pcard:hover .pos-pmedia img{transform:scale(1.07)}
.pos-pmedia .ph{width:100%;height:100%;display:grid;place-items:center;font-size:2.3rem;color:#8D6E63}
.pos-price{position:absolute;left:8px;bottom:8px;background:rgba(255,255,255,.95);color:#5D4037;padding:3px 11px;border-radius:999px;font-size:.78rem;font-weight:800;box-shadow:0 3px 8px rgba(0,0,0,.18)}
.pos-stock{position:absolute;right:8px;top:8px;font-size:.63rem;font-weight:800;padding:3px 8px;border-radius:999px;background:rgba(255,255,255,.94);letter-spacing:.02em}
.pos-stock.good{color:#1E7A4F}
.pos-stock.low{color:#C0392B}
.pos-stock.out{color:#777}
.pos-incart{position:absolute;right:8px;bottom:8px;background:linear-gradient(135deg,#E9A23B,#D1881F);color:#3E2723;font-weight:800;font-size:.72rem;min-width:24px;height:24px;padding:0 7px;border-radius:999px;display:none;place-items:center;box-shadow:0 3px 8px rgba(0,0,0,.3)}
.pos-incart.show{display:grid;animation:posPop .25s ease}
@keyframes posPop{0%{transform:scale(.4)}70%{transform:scale(1.15)}100%{transform:scale(1)}}

.pos-pbody{padding:10px 12px 12px;display:flex;flex-direction:column;gap:3px;flex:1}
.pos-pcat{font-size:.64rem;letter-spacing:.08em;text-transform:uppercase;color:#A08D80;font-weight:800}
.pos-pname{font-size:.9rem;font-weight:700;color:#33241E;margin:0;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.pos-ptag{font-size:.7rem;color:#8D6E63;font-weight:700}
.pos-pfoot{margin-top:auto;display:flex;gap:8px;align-items:center;padding-top:9px}
.pos-size-select{flex:1;min-width:0;border:2px solid #EEE0D2;border-radius:10px;padding:6px 6px;font-size:.73rem;font-weight:700;color:#5D4037;background:#FBF7F2;cursor:pointer}
.pos-size-select:focus{outline:none;border-color:#8D6E63}
.pos-add{border:none;background:linear-gradient(135deg,#5D4037,#3E2723);color:#fff;border-radius:10px;padding:7px 14px;font-size:.76rem;font-weight:800;display:flex;align-items:center;gap:5px;cursor:pointer;transition:.2s;white-space:nowrap}
.pos-add:hover{transform:translateY(-1px);box-shadow:0 7px 15px rgba(93,64,55,.35)}
.pos-add:disabled{background:#CFC3BB;cursor:not-allowed;box-shadow:none;transform:none}
.pos-add.full{flex:1;justify-content:center}
.pos-empty-products{grid-column:1/-1;text-align:center;padding:56px 20px;color:#A08D80}
.pos-empty-products i{font-size:3rem;opacity:.45;display:block;margin-bottom:8px}

.pos-cart{width:400px;min-width:360px;background:#fff;border-left:1px solid #EFE5DA;display:flex;flex-direction:column;min-height:0;box-shadow:-8px 0 26px rgba(62,39,35,.07);position:relative;z-index:10}
.pos-cart-head{padding:13px 18px;background:linear-gradient(135deg,#3E2723,#6F4E37);color:#fff;display:flex;justify-content:space-between;align-items:center;gap:10px}
.pos-cart-head h3{margin:0;font-size:1rem;font-weight:800;display:flex;align-items:center;gap:8px}
.pos-cart-head .sub{display:block;font-size:.7rem;opacity:.72;font-weight:600;margin-top:2px}
.pos-cart-close{display:none;background:rgba(255,255,255,.18);border:none;color:#fff;width:32px;height:32px;border-radius:50%;font-size:1.15rem;cursor:pointer;align-items:center;justify-content:center}
.pos-cart-items{flex:1;min-height:90px;overflow-y:auto;padding:4px 18px}
.pos-cart-empty{height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;color:#B9A99C;gap:4px;padding:24px}
.pos-cart-empty i{font-size:3.2rem;opacity:.4}
.pos-cart-empty b{color:#8D7B6E;font-size:.95rem}
.pos-cart-empty span{font-size:.8rem}

.pos-citem{display:grid;grid-template-columns:1fr auto;gap:6px 12px;padding:12px 0;border-bottom:1px dashed #EFE5DA;align-items:center;animation:posFade .25s ease}
@keyframes posFade{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
.pos-citem-name{font-size:.87rem;font-weight:700;color:#33241E;line-height:1.3}
.pos-citem-meta{font-size:.72rem;color:#A08D80;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.pos-citem-remove{background:none;border:none;color:#C0392B;font-size:.72rem;font-weight:700;opacity:.55;cursor:pointer;padding:0}
.pos-citem-remove:hover{opacity:1;text-decoration:underline}
.pos-citem-right{display:flex;flex-direction:column;align-items:flex-end;gap:7px}
.pos-stepper{display:flex;align-items:center;gap:5px;background:#FBF7F2;border:1px solid #EEE0D2;border-radius:999px;padding:3px}
.pos-step{width:24px;height:24px;border-radius:50%;border:none;background:#fff;color:#5D4037;font-weight:800;cursor:pointer;display:grid;place-items:center;font-size:.82rem;box-shadow:0 1px 3px rgba(0,0,0,.14);transition:.15s;line-height:1}
.pos-step:hover{background:#5D4037;color:#fff}
.pos-step:disabled{background:#EFE7E0;color:#BCAEA4;cursor:not-allowed;box-shadow:none}
.pos-qty{min-width:22px;text-align:center;font-weight:800;font-size:.86rem;color:#33241E}
.pos-line{font-weight:800;font-size:.9rem;color:#5D4037}

.pos-cart-foot{border-top:1px solid #EFE5DA;background:#FFFDFB;padding:14px 16px 16px;max-height:62%;overflow-y:auto;flex-shrink:0}
.pos-label{font-size:.68rem;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:#A08D80;margin:0 0 7px}
.pos-block{margin-bottom:13px}
.pos-seg{display:flex;gap:6px;flex-wrap:wrap}
.pos-seg-btn{flex:1;min-width:64px;border:2px solid #EEE0D2;background:#fff;border-radius:10px;padding:7px 5px;font-size:.75rem;font-weight:700;color:#6D5B51;cursor:pointer;transition:.2s;display:flex;align-items:center;justify-content:center;gap:5px}
.pos-seg-btn:hover{border-color:#C9B6A8}
.pos-seg-btn.active{background:#5D4037;border-color:#5D4037;color:#fff;box-shadow:0 5px 12px rgba(93,64,55,.3)}
.pos-field{width:100%;border:2px solid #EEE0D2;border-radius:10px;padding:8px 12px;font-size:.85rem;background:#fff;color:#33241E;margin-bottom:8px}
.pos-field:last-child{margin-bottom:0}
.pos-field:focus{outline:none;border-color:#8D6E63;box-shadow:0 0 0 4px rgba(141,110,99,.12)}
.pos-fields[hidden]{display:none}
.pos-two{display:flex;gap:8px}
.pos-two>*{flex:1;min-width:0}

.pos-totals{background:#FBF7F2;border:1px solid #EEE0D2;border-radius:14px;padding:11px 13px}
.pos-trow{display:flex;justify-content:space-between;align-items:center;font-size:.85rem;padding:3px 0;color:#6D5B51;font-weight:600}
.pos-trow .v{font-weight:700;color:#4A3B33}
.pos-trow.disc .v{color:#C0392B}
.pos-trow.total{border-top:2px dashed #E0D2C4;margin-top:7px;padding-top:9px;font-size:.8rem;font-weight:800;color:#3E2723;letter-spacing:.06em}
.pos-trow.total .v{font-size:1.35rem;font-weight:800;color:#5D4037;letter-spacing:0}
.pos-checkout{margin-top:12px;width:100%;border:none;border-radius:14px;padding:13px;font-size:1rem;font-weight:800;color:#fff;background:linear-gradient(135deg,#6F4E37,#3E2723);cursor:pointer;transition:.25s;display:flex;align-items:center;justify-content:center;gap:9px;box-shadow:0 8px 20px rgba(93,64,55,.32)}
.pos-checkout:hover{transform:translateY(-2px);box-shadow:0 14px 28px rgba(93,64,55,.42)}
.pos-checkout:disabled{background:#CFC3BB;box-shadow:none;transform:none;cursor:not-allowed}

.pos-fab{display:none;position:fixed;right:18px;bottom:18px;z-index:1600;border:none;border-radius:999px;padding:13px 20px;background:linear-gradient(135deg,#6F4E37,#3E2723);color:#fff;font-weight:800;font-size:.9rem;box-shadow:0 12px 26px rgba(62,39,35,.45);align-items:center;gap:9px;cursor:pointer}
.pos-fab .badge-x{background:#E9A23B;color:#3E2723;border-radius:999px;padding:1px 8px;font-size:.75rem;font-weight:800}
.pos-backdrop{display:none;position:fixed;inset:0;background:rgba(30,20,16,.52);z-index:1500;backdrop-filter:blur(2px)}
.pos-backdrop.show{display:block}

@media(max-width:1200px){
  .pos-cart{position:fixed;top:56px;right:0;bottom:0;width:min(430px,100%);min-width:0;z-index:1550;transform:translateX(106%);transition:transform .35s cubic-bezier(.4,0,.2,1);border-left:none;box-shadow:-14px 0 40px rgba(0,0,0,.25)}
  .pos-cart.open{transform:none}
  .pos-cart-close{display:flex}
  .pos-cart-foot{max-height:none}
  .pos-fab{display:flex}
  body.pos-cart-open .pos-fab{display:none}
}
@media(max-width:992px){.pos-layout{margin-left:0}}
@media(max-width:768px){
  .pos-topbar{flex-direction:column;align-items:flex-start;gap:9px;padding:11px 14px}
  .pos-toolbar{padding:10px 14px;gap:10px}
  .pos-search{max-width:none}
  .pos-scroll{padding:14px}
  .pos-grid{grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px}
  .pos-pmedia{height:112px}
}
@media(max-width:480px){.pos-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}}
</style>
</head>
<body class="pos-page">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="pos-layout">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<main class="pos-shell">

  <section class="pos-catalog">
    <div class="pos-topbar">
      <div class="pos-topbar-title">
        <div class="ico"><i class="bi bi-cash-stack"></i></div>
        <div>
          <h1>Point of Sale</h1>
          <div class="sub"><span id="posDate"><?php echo date('D, d M Y'); ?></span> &middot; <span id="posClock">--:--</span></div>
        </div>
      </div>
      <div class="pos-topbar-right">
        <div class="pos-pill"><i class="bi bi-box-seam"></i> <span id="hdrCount"><?php echo $cartCount; ?></span> item<span id="hdrCountS"><?php echo $cartCount === 1 ? '' : 's'; ?></span></div>
        <div class="pos-pill dark"><i class="bi bi-currency-dollar"></i> <span id="hdrTotal"><?php echo formatMoney($grandTotal); ?></span></div>
      </div>
    </div>

    <div class="pos-toolbar">
      <div class="pos-search">
        <i class="bi bi-search"></i>
        <input type="text" id="productSearch" placeholder="Search coffee, food, drinks..." autocomplete="off" oninput="applyFilters()">
      </div>
      <div class="pos-chips" id="categoryFilters">
        <button type="button" class="pos-chip active" data-cat="all" onclick="filterCategory('all')"><i class="bi bi-grid-3x3-gap-fill"></i> All</button>
        <?php foreach ($categories as $c): ?>
        <button type="button" class="pos-chip" data-cat="<?php echo $c['id']; ?>" onclick="filterCategory(<?php echo $c['id']; ?>)"><?php echo htmlspecialchars($c['name']); ?></button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="pos-scroll">
      <p class="pos-count" id="productCount"><?php echo count($products); ?> products available</p>
      <div class="pos-grid" id="productsRow">
        <?php foreach ($products as $p):
            $hasImage = !empty($p['image']) && file_exists(__DIR__ . '/../public/uploads/products/' . $p['image']);
            $imgPath = $baseUrl . '/public/uploads/products/' . htmlspecialchars($p['image']);
            $out = $p['stock_quantity'] <= 0;
            $stockClass = $p['stock_quantity'] > 10 ? 'good' : ($out ? 'out' : 'low');
            $stockText = $p['stock_quantity'] > 10
                ? $p['stock_quantity'] . ' in stock'
                : ($out ? 'Out of stock' : 'Low: ' . $p['stock_quantity']);
            $pSizes = getProductSizes($p['size']);
            $pShort = sizeShortLabel($p['size']);
        ?>
        <article class="pos-pcard<?php echo $out ? ' out' : ''; ?>"
                 data-id="<?php echo $p['id']; ?>"
                 data-category="<?php echo $p['category_id']; ?>"
                 data-name="<?php echo strtolower(htmlspecialchars($p['name'] . ' ' . $p['category_name'])); ?>"
                 onclick="cardAdd(event, <?php echo $p['id']; ?>)">
          <div class="pos-pmedia">
            <?php if ($hasImage): ?>
              <img src="<?php echo $imgPath; ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='grid'">
              <div class="ph" style="display:none"><i class="bi bi-cup-hot"></i></div>
            <?php else: ?>
              <div class="ph"><i class="bi bi-cup-hot"></i></div>
            <?php endif; ?>
            <span class="pos-price"><?php echo formatMoney($p['price']); ?></span>
            <span class="pos-stock <?php echo $stockClass; ?>"><?php echo $stockText; ?></span>
            <span class="pos-incart" data-badge="<?php echo $p['id']; ?>"></span>
          </div>
          <div class="pos-pbody">
            <div class="pos-pcat"><?php echo htmlspecialchars($p['category_name']); ?></div>
            <h6 class="pos-pname"><?php echo htmlspecialchars($p['name']); ?></h6>
            <?php if ($pShort !== ''): ?><div class="pos-ptag"><i class="bi bi-rulers"></i> <?php echo htmlspecialchars($pShort); ?></div><?php endif; ?>
            <div class="pos-pfoot">
              <?php if (!empty($pSizes)): ?>
                <select class="pos-size-select" data-pid="<?php echo $p['id']; ?>" onclick="event.stopPropagation()" <?php echo $out ? 'disabled' : ''; ?>>
                  <?php echo sizeOptionsHtml($p['size'], '', 'Size'); ?>
                </select>
                <button type="button" class="pos-add" <?php echo $out ? 'disabled' : ''; ?> onclick="event.stopPropagation();addToPosCart(<?php echo $p['id']; ?>)"><i class="bi bi-plus-lg"></i> Add</button>
              <?php else: ?>
                <button type="button" class="pos-add full" <?php echo $out ? 'disabled' : ''; ?> onclick="event.stopPropagation();addToPosCart(<?php echo $p['id']; ?>)"><i class="bi bi-plus-lg"></i> Add</button>
              <?php endif; ?>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
        <div class="pos-empty-products" id="noProducts" hidden>
          <i class="bi bi-search-heart"></i>
          <b>No products found</b>
          <div style="font-size:.85rem">Try another search or category</div>
        </div>
      </div>
    </div>
  </section>

  <aside class="pos-cart" id="posCart">
    <div class="pos-cart-head">
      <div>
        <h3><i class="bi bi-receipt-cutoff"></i> Current Order</h3>
        <span class="sub"><?php echo date('Y-m-d'); ?> &middot; Cashier: <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Staff'); ?></span>
      </div>
      <button type="button" class="pos-cart-close" onclick="togglePosCart(false)" aria-label="Close">&times;</button>
    </div>

    <div class="pos-cart-items" id="posCartItems"></div>

    <div class="pos-cart-foot">
      <div class="pos-block">
        <div class="pos-label">Order Type</div>
        <div class="pos-seg" id="orderTypeGroup">
          <button type="button" class="pos-seg-btn active" data-type="dine_in" onclick="selectOrderType('dine_in')"><i class="bi bi-shop"></i> Dine In</button>
          <button type="button" class="pos-seg-btn" data-type="takeaway" onclick="selectOrderType('takeaway')"><i class="bi bi-bag"></i> Takeaway</button>
          <button type="button" class="pos-seg-btn" data-type="delivery" onclick="selectOrderType('delivery')"><i class="bi bi-truck"></i> Delivery</button>
        </div>
      </div>

      <div class="pos-fields" id="dineInFields">
        <div class="pos-label">Table Number</div>
        <input type="number" id="tableNumber" class="pos-field" placeholder="e.g. 5" min="1">
      </div>

      <div class="pos-fields" id="deliveryFields" hidden>
        <div class="pos-label">Delivery Details</div>
        <div class="pos-two" style="margin-bottom:8px">
          <input type="text" id="deliveryName" class="pos-field" style="margin:0" placeholder="Customer name">
          <input type="text" id="deliveryPhone" class="pos-field" style="margin:0" placeholder="Phone">
        </div>
        <input type="text" id="deliveryAddress" class="pos-field" placeholder="Delivery address">
      </div>

      <div class="pos-block">
        <div class="pos-label">Payment Method</div>
        <div class="pos-seg" id="paymentGroup">
          <?php foreach ($paymentOptions as $pm => $opt): if (!$paymentVisible[$pm]) continue; ?>
          <button type="button" class="pos-seg-btn<?php echo $pm === $activePayment ? ' active' : ''; ?>" data-method="<?php echo $pm; ?>" onclick="selectPayment('<?php echo $pm; ?>')"><i class="bi <?php echo $opt[0]; ?>"></i> <?php echo $opt[1]; ?></button>
          <?php endforeach; ?>
        </div>
      </div>

      <?php if ($paymentVisible['qr']): ?>
      <div class="pos-block" id="qrPayBox" <?php echo $activePayment === 'qr' ? '' : 'hidden'; ?>>
        <div class="pos-label"><i class="bi bi-qr-code"></i> QR Payment</div>
        <div style="background:#fff;border-radius:12px;padding:12px;text-align:center">
          <div style="display:flex;justify-content:center;gap:14px;align-items:center;flex-wrap:wrap">
            <div>
              <div id="posQrCode" style="display:flex;justify-content:center;min-height:152px"><?php if ($qrImageSrc !== ''): ?><img src="<?php echo $qrImageSrc; ?>" alt="Payment QR" style="width:152px;height:152px;object-fit:contain;border:1px solid #EEE0D2;border-radius:10px;background:#fff"><?php endif; ?></div>
            </div>
            <div style="text-align:left">
              <div style="font-size:.64rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#A08D80">Amount to pay</div>
              <div style="font-size:1.4rem;font-weight:800;color:#5D4037;line-height:1.2" id="posQrAmount"><?php echo formatMoney($grandTotal); ?></div>
              <div style="font-size:11px;color:#888;margin-top:2px" id="posQrRef"></div>
              <div style="font-size:11px;color:#6f6f6f;margin-top:6px"><?php echo $qrImageSrc !== '' || $qrPayload !== '' ? 'Scan with your banking app - the price fills in automatically' : 'Press Scan QR to show the payment code'; ?></div>
            </div>
          </div>
          <?php if ($qrImageSrc === '' && $qrPayload === ''): ?>
          <div style="font-size:11px;color:#c0392b;margin-top:8px"><i class="bi bi-exclamation-triangle"></i> No store QR uploaded - add it in Admin &rarr; Settings &rarr; Payment Settings.</div>
          <?php endif; ?>
          <div class="pos-seg" style="margin-top:10px">
            <button type="button" class="pos-seg-btn" id="posQrScanBtn" onclick="qrStartScan()"><i class="bi bi-upc-scan"></i> Scan QR</button>
          </div>
          <div style="font-size:11px;color:#777;margin-top:6px" id="posQrHint"><?php echo ($qrImageSrc !== '' || $qrPayload !== '') ? 'Press Scan QR - the customer pays with the banking app, then press Payment received.' : 'Press Scan QR - show the payment code to the customer.'; ?></div>
        </div>
      </div>
      <?php endif; ?>

      <div class="pos-block">
        <div class="pos-label">Discount</div>
        <div class="pos-two">
          <input type="number" id="posDiscInput" class="pos-field" style="margin:0" placeholder="0" min="0" max="100" value="<?php echo $defaultDiscount > 0 ? $defaultDiscount : ''; ?>" oninput="recalcTotals()">
          <div class="pos-pill" style="justify-content:center;margin:0" id="discHint">No discount</div>
        </div>
      </div>

      <div class="pos-totals">
        <div class="pos-trow"><span>Subtotal</span><span class="v" id="sumSub"><?php echo formatMoney($total); ?></span></div>
        <div class="pos-trow"><span>Tax (<?php echo $taxLabel; ?>%)</span><span class="v" id="sumTax"><?php echo formatMoney($taxAmt); ?></span></div>
        <div class="pos-trow disc"><span>Discount</span><span class="v" id="sumDisc">-<?php echo formatMoney(0); ?></span></div>
        <div class="pos-trow total"><span>TOTAL</span><span class="v" id="sumTotal"><?php echo formatMoney($grandTotal); ?></span></div>
      </div>

      <button type="button" class="pos-checkout" id="checkoutBtn" onclick="completeOrder()">
        <i class="bi bi-check2-circle"></i> Complete Payment
      </button>
    </div>
  </aside>

</main>
</div>

<div class="pos-backdrop" id="posBackdrop" onclick="togglePosCart(false)"></div>
<button type="button" class="pos-fab" id="posFab" onclick="togglePosCart(true)">
  <i class="bi bi-cart3"></i> View Order <span class="badge-x" id="fabCount"><?php echo $cartCount; ?></span> <span id="fabTotal"><?php echo formatMoney($grandTotal); ?></span>
</button>

<script>
var posProducts = <?php echo json_encode($posProducts, JSON_UNESCAPED_SLASHES); ?>;
var posCart = <?php echo json_encode($cart); ?>;
var taxRate = <?php echo json_encode($taxRate); ?>;
var moneySym = <?php echo json_encode($moneySym); ?>;
var currentCat = 'all';
var orderType = 'dine_in';
var paymentMethod = <?php echo json_encode($activePayment); ?>;
var qrStore = <?php echo json_encode(siteName()); ?>;
var qrCurrency = <?php echo json_encode(setting('currency', 'USD')); ?>;
var storeQrSrc = <?php echo json_encode($qrImageSrc); ?>;
var storeQrPayload = <?php echo json_encode($qrPayload); ?>;
var qrState = { amount: null, ref: '', scanned: false, payload: '', session: null };
var lastTotal = 0;

function fmt(v){ v = Number(v) || 0; var s = Math.abs(v).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); return (v < 0 ? '-' : '') + moneySym + s; }
function esc(s){ return String(s == null ? '' : s).replace(/[&<>"']/g, function(m){ return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[m]; }); }
function posErr(e){ var m = (e && e.message) ? e.message : 'Network error - check your connection'; return m.length > 160 ? m.slice(0, 160) + '...' : m; }
function posFetch(url, body){
  return fetch(url, { method:'POST', headers:{ 'Content-Type':'application/x-www-form-urlencoded' }, body: body || '', credentials:'include' })
    .then(function(r){ return r.text().then(function(t){
      var j = null;
      try { j = JSON.parse(t); } catch (e) {
        console.error('POS: non-JSON response from ' + url, t);
        var clean = t.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
        throw new Error(clean ? clean.slice(0, 160) : 'Server error (HTTP ' + r.status + ')');
      }
      if (!r.ok) throw new Error((j && j.error) ? j.error : 'Server error (HTTP ' + r.status + ')');
      return j;
    }); });
}
function posNotify(msg, type){ if (typeof showToast === 'function') { showToast(msg, type || 'info'); } else { alert(msg); } }

function updateQrBox(total){
  var amtEl = document.getElementById('posQrAmount');
  if (!amtEl) return;
  if (qrState.amount === null || Number(qrState.amount) !== Number(total)) {
    qrState.amount = Number(total);
    qrState.scanned = false;
    qrState.payload = '';
    if (qrState.session && Number(Number(qrState.session.amount).toFixed(2)) !== Number(Number(total).toFixed(2))) {
      qrSessionCancel();
    }
  }
  amtEl.textContent = fmt(total);
  var refEl = document.getElementById('posQrRef');
  if (refEl) refEl.textContent = qrState.session ? 'Ref: ' + qrState.session.ref : '';
  var box = document.getElementById('posQrCode');
  if (box && storeQrPayload) {
    var generated = QrPay.withAmount(storeQrPayload, Number(total), qrCurrency);
    if (generated && generated !== qrState.payload) {
      qrState.payload = generated;
      QrPay.render(box, generated, 152);
    }
  } else if (box && !storeQrSrc) {
    var payload = QrPay.payload({ store: qrStore, ref: qrState.session ? qrState.session.ref : '', amountText: fmt(total), currency: qrCurrency });
    if (payload !== qrState.payload) {
      qrState.payload = payload;
      QrPay.render(box, payload, 152);
    }
  }
  syncQrBox();
}

function syncQrBox(){
  var scanBtn = document.getElementById('posQrScanBtn');
  var hint = document.getElementById('posQrHint');
  if (scanBtn) {
    scanBtn.innerHTML = qrState.scanned
      ? '<i class="bi bi-check-lg"></i> Paid'
      : '<i class="bi bi-upc-scan"></i> Scan QR';
    scanBtn.disabled = qrState.scanned;
  }
  if (hint) {
    hint.textContent = qrState.scanned
      ? 'Payment confirmed - completing the order.'
      : (storeQrSrc || storeQrPayload
          ? 'Press Scan QR - the customer pays with the banking app, then press Payment received.'
          : 'Press Scan QR - show the payment code to the customer.');
  }
  syncCheckoutBtn();
}

function syncCheckoutBtn(){
  var b = document.getElementById('checkoutBtn');
  if (!b) return;
  if (paymentMethod !== 'qr') {
    b.innerHTML = '<i class="bi bi-check2-circle"></i> Complete Payment';
    return;
  }
  b.innerHTML = qrState.scanned
    ? '<i class="bi bi-check2-circle"></i> Completing...'
    : '<i class="bi bi-qr-code"></i> Scan QR Code';
}

function orderFormValid(){
  if (recalcTotals().count === 0) { posNotify('Cart is empty', 'warning'); return false; }
  var tableNum = document.getElementById('tableNumber').value;
  var dName = document.getElementById('deliveryName').value.trim();
  var dPhone = document.getElementById('deliveryPhone').value.trim();
  if (orderType === 'dine_in' && !tableNum) { posNotify('Please enter the table number', 'warning'); document.getElementById('tableNumber').focus(); return false; }
  if (orderType === 'delivery' && (!dName || !dPhone)) { posNotify('Please fill in customer name and phone', 'warning'); return false; }
  return true;
}

function qrSessionCancel(){
  var s = qrState.session;
  qrState.session = null;
  qrState.ref = '';
  if (s && s.state === 'pending') {
    posFetch('<?php echo $baseUrl; ?>/ajax/pay.php?action=cancel', 'ref=' + encodeURIComponent(s.ref)).catch(function(){});
  }
  var refEl = document.getElementById('posQrRef');
  if (refEl) refEl.textContent = '';
}

function qrSessionStatus(){
  if (!qrState.session) return Promise.resolve({ success: false, state: 'not_found' });
  return posFetch('<?php echo $baseUrl; ?>/ajax/pay.php?action=status&ref=' + encodeURIComponent(qrState.session.ref))
    .then(function(d){ if (qrState.session) qrState.session.state = d.state; return d; });
}

function qrConfirmPaid(){
  var s = qrState.session;
  if (!s) return Promise.reject(new Error('No QR session - close this and press Scan QR again'));
  if (s.state === 'completed') return Promise.resolve(s);
  return posFetch('<?php echo $baseUrl; ?>/pay.php?action=confirm', 'ref=' + encodeURIComponent(s.ref))
    .then(function(d){
      if (!d || !d.success) throw new Error((d && d.error) || 'Could not confirm the payment');
      s.state = 'completed';
      s.order = d.order || s.order || '';
      return s;
    });
}

function qrEnsureSession(t){
  var want = Number(Number(t.total).toFixed(2));
  if (qrState.session && Number(Number(qrState.session.amount).toFixed(2)) === want) {
    return Promise.resolve(qrState.session);
  }
  qrSessionCancel();
  return posFetch('<?php echo $baseUrl; ?>/ajax/pay.php?action=create', 'source=pos&discount=' + encodeURIComponent(t.pct))
    .then(function(d){
      if (!d.success) throw new Error(d.error || 'Could not start the QR payment');
      qrState.session = { ref: d.ref, amount: d.amount, state: d.state, pay_url: d.pay_url };
      qrState.ref = d.ref;
      var refEl = document.getElementById('posQrRef');
      if (refEl) refEl.textContent = 'Ref: ' + d.ref;
      return qrState.session;
    });
}

function qrOpenPopup(){
  var s = qrState.session;
  if (!s) return;
  QrPay.openScan({
    ref: s.ref,
    amountText: s.amount_text || fmt(s.amount),
    currency: qrCurrency,
    payload: s.pay_url,
    amount: Number(s.amount),
    mainSize: 232,
    bankQr: storeQrSrc,
    bankQrPayload: storeQrPayload,
    bankQrCurrency: qrCurrency,
    hint: (storeQrSrc || storeQrPayload)
      ? 'Pay with the banking app, then press Payment received.'
      : 'Show this code to the customer.',
    pollMs: 1500,
    statusFn: qrSessionStatus,
    confirmFn: qrConfirmPaid,
    doneLabel: 'Payment received',
    onClose: function(){ qrSessionCancel(); },
    onDone: function(){
      qrState.scanned = true;
      syncQrBox();
      completeOrder();
    }
  });
}

function qrStartScan(){
  if (qrState.scanned) return;
  if (!orderFormValid()) return;
  var t = recalcTotals();
  var s = qrState.session;
  if (s && s.state === 'completed' && Number(Number(s.amount).toFixed(2)) === Number(Number(t.total).toFixed(2))) {
    qrState.scanned = true;
    syncQrBox();
    completeOrder();
    return;
  }
  qrEnsureSession(t)
    .then(function(){ qrOpenPopup(); })
    .catch(function(e){ posNotify(posErr(e), 'error'); });
}

function cartLines(){
  var lines = [], count = 0, sub = 0;
  Object.keys(posCart).forEach(function(id){
    var item = posCart[id];
    var qty = item && typeof item === 'object' ? Number(item.qty) : Number(item);
    var size = item && typeof item === 'object' ? (item.size || '') : '';
    var p = posProducts[id];
    if (!p || qty <= 0) return;
    lines.push({ id: id, qty: qty, size: size, p: p, line: p.price * qty });
    count += qty;
    sub += p.price * qty;
  });
  return { lines: lines, count: count, sub: sub };
}

function recalcTotals(){
  var c = cartLines();
  var pct = parseFloat(document.getElementById('posDiscInput').value) || 0;
  if (pct < 0) pct = 0;
  if (pct > 100) pct = 100;
  var tax = c.sub * (taxRate / 100);
  var disc = c.sub * (pct / 100);
  var total = c.sub + tax - disc;
  document.getElementById('sumSub').textContent = fmt(c.sub);
  document.getElementById('sumTax').textContent = fmt(tax);
  document.getElementById('sumDisc').textContent = '-' + fmt(disc);
  document.getElementById('sumTotal').textContent = fmt(total);
  document.getElementById('hdrCount').textContent = c.count;
  document.getElementById('hdrCountS').textContent = c.count === 1 ? '' : 's';
  document.getElementById('hdrTotal').textContent = fmt(total);
  document.getElementById('fabCount').textContent = c.count;
  document.getElementById('fabTotal').textContent = fmt(total);
  document.getElementById('discHint').textContent = pct > 0 ? pct + '% off' : 'No discount';
  document.getElementById('checkoutBtn').disabled = c.count === 0;
  lastTotal = total;
  updateQrBox(total);
  return { sub: c.sub, tax: tax, disc: disc, total: total, count: c.count, pct: pct };
}

function renderCart(){
  var c = cartLines();
  var box = document.getElementById('posCartItems');
  if (!c.lines.length) {
    box.innerHTML = '<div class="pos-cart-empty"><i class="bi bi-cart-x"></i><b>No items yet</b><span>Tap a product to start an order</span></div>';
  } else {
    box.innerHTML = c.lines.map(function(l){
      var maxed = l.qty >= l.p.stock;
      return '<div class="pos-citem">' +
        '<div><div class="pos-citem-name">' + esc(l.p.name) + '</div>' +
        '<div class="pos-citem-meta">' + (l.size ? '<span>' + esc(l.size) + '</span>' : '') +
        '<span>' + fmt(l.p.price) + ' each</span>' +
        '<button type="button" class="pos-citem-remove" onclick="posRemove(' + l.id + ')">Remove</button></div></div>' +
        '<div class="pos-citem-right">' +
        '<div class="pos-stepper">' +
        '<button type="button" class="pos-step" onclick="posQty(' + l.id + ',-1)"><i class="bi bi-dash"></i></button>' +
        '<span class="pos-qty">' + l.qty + '</span>' +
        '<button type="button" class="pos-step" ' + (maxed ? 'disabled' : '') + ' onclick="posQty(' + l.id + ',1)"><i class="bi bi-plus"></i></button>' +
        '</div>' +
        '<div class="pos-line">' + fmt(l.line) + '</div>' +
        '</div></div>';
    }).join('');
  }
  document.querySelectorAll('[data-badge]').forEach(function(b){
    var item = posCart[b.dataset.badge];
    var q = item ? (typeof item === 'object' ? item.qty : item) : 0;
    b.textContent = q > 0 ? 'x' + q : '';
    b.classList.toggle('show', q > 0);
  });
  recalcTotals();
}

function cardAdd(e, pid){
  if (e.target.closest('.pos-size-select') || e.target.closest('button')) return;
  addToPosCart(pid);
}

function addToPosCart(pid){
  var p = posProducts[pid];
  if (!p) return;
  if (p.stock <= 0) { posNotify('Out of stock', 'error'); return; }
  var sel = document.querySelector('.pos-size-select[data-pid="' + pid + '"]');
  if (sel && !sel.value) {
    sel.style.borderColor = '#C0392B';
    sel.focus();
    posNotify('Please select a size', 'warning');
    setTimeout(function(){ sel.style.borderColor = ''; }, 1500);
    return;
  }
  var size = sel ? sel.value : '';
  var inCart = posCart[pid] ? (typeof posCart[pid] === 'object' ? posCart[pid].qty : posCart[pid]) : 0;
  if (inCart + 1 > p.stock) { posNotify('Only ' + p.stock + ' left in stock', 'error'); return; }
  posFetch('<?php echo $baseUrl; ?>/ajax/pos.php?action=add',
    'product_id=' + pid + '&quantity=1&size=' + encodeURIComponent(size)
  ).then(function(d){
    if (d.success) {
      posCart = d.cart || posCart;
      renderCart();
      posNotify(esc(p.name) + ' added to order', 'success');
    } else { posNotify(d.error || 'Could not add item', 'error'); }
  }).catch(function(e){ posNotify(posErr(e), 'error'); });
}

function posQty(pid, delta){
  var p = posProducts[pid];
  var cur = posCart[pid] ? (typeof posCart[pid] === 'object' ? posCart[pid].qty : posCart[pid]) : 0;
  if (delta > 0 && p && cur + delta > p.stock) { posNotify('Only ' + p.stock + ' in stock', 'error'); return; }
  posFetch('<?php echo $baseUrl; ?>/ajax/pos.php?action=update',
    'product_id=' + pid + '&delta=' + delta
  ).then(function(d){
    if (d.success) { posCart = d.cart || posCart; renderCart(); }
    else { posNotify(d.error || 'Update failed', 'error'); }
  }).catch(function(e){ posNotify(posErr(e), 'error'); });
}

function posRemove(pid){
  posFetch('<?php echo $baseUrl; ?>/ajax/pos.php?action=remove&id=' + pid, '').then(function(d){
    if (d.success) { delete posCart[pid]; renderCart(); posNotify('Item removed', 'info'); }
    else { posNotify(d.error || 'Remove failed', 'error'); }
  }).catch(function(e){ posNotify(posErr(e), 'error'); });
}

function applyFilters(){
  var q = (document.getElementById('productSearch').value || '').toLowerCase().trim();
  var shown = 0;
  document.querySelectorAll('.pos-pcard').forEach(function(el){
    var okCat = currentCat === 'all' || el.dataset.category == currentCat;
    var okText = !q || el.dataset.name.indexOf(q) !== -1;
    var show = okCat && okText;
    el.style.display = show ? '' : 'none';
    if (show) shown++;
  });
  document.getElementById('productCount').textContent = shown + (shown === 1 ? ' product' : ' products') + ' available';
  document.getElementById('noProducts').hidden = shown !== 0;
}

function filterCategory(cat){
  currentCat = cat;
  document.querySelectorAll('#categoryFilters .pos-chip').forEach(function(b){ b.classList.remove('active'); });
  var btn = document.querySelector('#categoryFilters [data-cat="' + cat + '"]');
  if (btn) btn.classList.add('active');
  applyFilters();
}

function selectOrderType(type){
  orderType = type;
  document.querySelectorAll('#orderTypeGroup .pos-seg-btn').forEach(function(b){ b.classList.remove('active'); });
  var btn = document.querySelector('#orderTypeGroup [data-type="' + type + '"]');
  if (btn) btn.classList.add('active');
  document.getElementById('dineInFields').hidden = type !== 'dine_in';
  document.getElementById('deliveryFields').hidden = type !== 'delivery';
}

function selectPayment(method){
  paymentMethod = method;
  document.querySelectorAll('#paymentGroup .pos-seg-btn').forEach(function(b){ b.classList.remove('active'); });
  var btn = document.querySelector('#paymentGroup [data-method="' + method + '"]');
  if (btn) btn.classList.add('active');
  var qb = document.getElementById('qrPayBox');
  if (qb) qb.hidden = (method !== 'qr');
  if (method === 'qr') {
    updateQrBox(lastTotal);
  } else {
    qrState.scanned = false;
    if (qrState.session) qrSessionCancel();
    syncQrBox();
  }
}

function togglePosCart(open){
  var cart = document.getElementById('posCart');
  var bd = document.getElementById('posBackdrop');
  var willOpen = typeof open === 'boolean' ? open : !cart.classList.contains('open');
  cart.classList.toggle('open', willOpen);
  bd.classList.toggle('show', willOpen);
  document.body.classList.toggle('pos-cart-open', willOpen);
}

function completeOrder(){
  var t = recalcTotals();
  if (!orderFormValid()) return;
  var tableNum = document.getElementById('tableNumber').value;
  var dName = document.getElementById('deliveryName').value.trim();
  var dPhone = document.getElementById('deliveryPhone').value.trim();
  var dAddr = document.getElementById('deliveryAddress').value.trim();

  if (paymentMethod === 'qr' && !qrState.scanned) {
    qrStartScan();
    return;
  }

  var btn = document.getElementById('checkoutBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

  var body = 'order_type=' + orderType +
    '&payment=' + paymentMethod +
    '&table_number=' + encodeURIComponent(tableNum) +
    '&delivery_name=' + encodeURIComponent(dName) +
    '&delivery_phone=' + encodeURIComponent(dPhone) +
    '&delivery_address=' + encodeURIComponent(dAddr) +
    '&discount=' + t.pct +
    '&qr_ref=' + encodeURIComponent(qrState.session ? qrState.session.ref : '');

  posFetch('<?php echo $baseUrl; ?>/ajax/pos.php?action=checkout', body).then(function(d){
    if (d.success) {
      if (paymentMethod === 'qr') {
        QrPay.successScreen({ amountText: fmt(t.total), currency: qrCurrency, ref: qrState.ref, order: d.order_number, message: 'Order created and paid.', duration: 3200, onEnd: function(){ location.reload(); } });
      } else {
        posNotify('Order ' + d.order_number + ' completed!', 'success');
        setTimeout(function(){ location.reload(); }, 900);
      }
    } else {
      posNotify(d.error || 'Checkout failed', 'error');
      resetCheckoutBtn();
    }
  }).catch(function(e){
    posNotify(posErr(e), 'error');
    resetCheckoutBtn();
  });
}

function resetCheckoutBtn(){
  var b = document.getElementById('checkoutBtn');
  if (!b) return;
  b.disabled = false;
  syncCheckoutBtn();
}

function tickClock(){
  var el = document.getElementById('posClock');
  if (el) el.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

document.addEventListener('keydown', function(e){
  if (e.key === 'Escape') togglePosCart(false);
});
tickClock();
setInterval(tickClock, 1000);
renderCart();
</script>
<?php include __DIR__ . '/../includes/footer-admin.php'; ?>
