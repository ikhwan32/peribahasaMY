<footer class="bg-light py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5><?php echo SITE_NAME; ?></h5>
                <p class="text-muted">
                    Platform untuk mengumpul, berkongsi, dan mempelajari peribahasa Melayu.
                </p>
            </div>
            <div class="col-md-3">
                <h5>Pautan</h5>
                <ul class="list-unstyled">
                    <li><a href="<?php echo url('index.php'); ?>" class="text-decoration-none">Utama</a></li>
                    <li><a href="<?php echo url('search.php'); ?>" class="text-decoration-none">Carian</a></li>
                    <?php if (is_logged_in()): ?>
                        <li><a href="<?php echo url('submit.php'); ?>" class="text-decoration-none">Hantar Peribahasa</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="col-md-3">
                <h5>Hubungi Kami</h5>
                <ul class="list-unstyled">
                    <li><a href="mailto:admin@peribahasa.com" class="text-decoration-none">admin@eperibahasa.com</a></li>
                </ul>
            </div>
        </div>
        <hr>
        <div class="text-center">
            <small class="text-muted">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Hak cipta terpelihara.</small>
        </div>
    </div>
</footer>
