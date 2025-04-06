tailwind.config = {
    theme: {
    extend: {
        fontFamily: {
        'playfair': ['"Playfair Display"', 'serif'],
        'alexbrush': ['"Alex Brush"', 'cursive'],
        'inter': ['Inter', 'sans-serif'],
        'cinzel': ['Cinzel', 'serif'],
        'hedvig': ['Hedvig Letters Serif', 'serif']
        },
        colors: {
        'yellow': {
            600: '#CA8A04',
        },
        'navy': '#F0F4F8',         // Changed from dark to light blue-gray
        'cream': '#F9F6F0',
        'dark': '#4A5568',         // Changed to medium gray
        'gold': '#D69E2E',         // Lightened gold
        'darkgold': '#B7791F',     // Lightened dark gold
        'primary': '#F8FAFC',      // Changed from dark to very light gray
        'primary-foreground': '#334155', // Changed to dark gray for text on light background
        'secondary': '#F1F5F9',
        'secondary-foreground': '#334155',
        'border': '#E4E9F0',
        'input-border': '#D3D8E1',
        'error': '#E53E3E',
        'success': '#38A169',
        'sidebar-bg': '#FFFFFF',   // New: White background for sidebar
        'sidebar-hover': '#F1F5F9', // New: Light gray for hover states
        'sidebar-text': '#334155', // New: Dark gray for sidebar text
        'sidebar-accent': '#CA8A04', // Keeping the gold accent
        'sidebar-border': '#E2E8F0', // New: Light border color
        },
        boxShadow: {
        'input': '0 1px 2px rgba(0, 0, 0, 0.05)',
        'card': '0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
        'sidebar': '0 0 15px rgba(0, 0, 0, 0.05)' // New: Light shadow for sidebar
        }
    }
    }
}