        </main>

        <footer class="footer">
            <p>Sportfest Manager © <?php echo date('Y'); ?></p>
        </footer>
    </div>

    <script src="<?php echo url_for('static/js/main.js'); ?>"></script>
    <?php if (isset($extra_js)) echo $extra_js; ?>
</body>
</html>
