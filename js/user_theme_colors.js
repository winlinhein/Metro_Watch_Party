(function () {
    function nx(token) {
        return 'rgb(var(--nx-' + token + ') / <alpha-value>)';
    }

    function scale(name, shades) {
        const out = {};
        shades.forEach(function (shade) {
            out[shade] = nx(name + '-' + shade);
        });
        return out;
    }

    window.nexusThemeColors = {
        black: nx('black'),
        red: scale('red', [300, 400, 500, 600, 900]),
        rose: scale('rose', [500, 600]),
        indigo: scale('indigo', [200, 300, 400, 500, 600]),
        violet: scale('violet', [400, 500, 600]),
        fuchsia: scale('fuchsia', [300, 400, 500, 600]),
        purple: scale('purple', [300, 400, 500, 600]),
        emerald: scale('emerald', [300, 400, 500, 600, 700])
    };

    window.NEXUS_THEMES = [
        { id: 'crimson', name: 'Crimson', blurb: 'Red into indigo', gradient: 'linear-gradient(120deg, #fb7185 0%, #ef4444 46%, #6366f1 100%)' },
        { id: 'arctic', name: 'Arctic', blurb: 'Ice into blue', gradient: 'linear-gradient(120deg, #67e8f9 0%, #22d3ee 42%, #3b82f6 100%)' },
        { id: 'amethyst', name: 'Amethyst', blurb: 'Magenta into violet', gradient: 'linear-gradient(120deg, #f0abfc 0%, #d946ef 44%, #8b5cf6 100%)' },
        { id: 'verdant', name: 'Verdant', blurb: 'Lime into teal', gradient: 'linear-gradient(120deg, #bef264 0%, #10b981 46%, #14b8a6 100%)' }
    ];
})();
