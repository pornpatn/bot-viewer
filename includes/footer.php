<script>
document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".family-header").forEach(header => {
    header.addEventListener("click", function () {
      const card = header.closest(".family-card");
      card.classList.toggle("collapsed");
    });
  });
});
</script>
</body>
</html>
