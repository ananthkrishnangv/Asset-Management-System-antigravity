            </main>
            
            <footer class="bg-white border-t border-gray-100 py-4 px-8 mt-auto">
                <div class="flex items-center justify-between text-sm text-gray-500">
                    <p>© <?= date('Y') ?> CSIR-SERC. All rights reserved.</p>
                </div>
            </footer>
        </div>
    </div>
    <!-- Common Scripts -->
    <script>
        function showToast(message, type = 'info') {
            // Toast logic
        }
    </script>
    <?= $additionalScripts ?? '' ?>
</body>
</html>
