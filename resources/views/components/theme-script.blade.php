<script>
    try {
        const theme = localStorage.getItem('theme') ?? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.dataset.bsTheme = theme;
    } catch {
        // Storage may be unavailable; fall back to the light theme.
    }
</script>
