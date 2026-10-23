<?php
/**
 * Public Layout Footer
 * app/views/layouts/public-footer.php
 *
 * Closes the public page layout, renders the footer component,
 * and loads Bootstrap and custom JavaScript.
 */

declare(strict_types=1);
?>
    </main><!-- /.public-main -->

    <!-- Footer Component -->
    <?php require_once APP_ROOT . '/app/views/components/public/footer.php'; ?>

</div><!-- /.public-page-wrapper -->

<!-- Bootstrap 5.3 Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Custom Vanilla JavaScript Foundation -->
<script src="<?= assetVersioned('js/main.js') ?>"></script>
</body>
</html>
