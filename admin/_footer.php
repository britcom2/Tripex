</div>
<script>
document.querySelectorAll('[data-confirm]').forEach(function (el) {
  el.addEventListener('submit', function (ev) { if (!confirm(el.getAttribute('data-confirm'))) ev.preventDefault(); });
});
</script>
</body>
</html>
