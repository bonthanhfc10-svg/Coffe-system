<div class="print-only report-print-foot">
    <div class="rpf-signs">
        <div class="rpf-sign"><span>Prepared by</span><b><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Staff'); ?></b></div>
        <div class="rpf-sign"><span>Checked by</span><b>&nbsp;</b></div>
        <div class="rpf-sign"><span>Approved by</span><b>&nbsp;</b></div>
    </div>
    <div class="rpf-note"><?php echo htmlspecialchars(siteName()); ?> &middot; Sales Report &middot; Printed <?php echo date('M d, Y H:i'); ?></div>
</div>
