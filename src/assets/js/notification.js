document.querySelectorAll('.dropdown-toggle').forEach(function(toggle) {
    toggle.addEventListener('click', function(e) {
        const currentToggle = this;

        document.querySelectorAll('.dropdown-toggle[aria-expanded="true"]').forEach(function(openToggle) {
            if (openToggle !== currentToggle) {
                bootstrap.Dropdown.getInstance(openToggle)?.hide();
            }
        });
    });
});