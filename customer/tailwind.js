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
                'navy': '#2D2B30',
                'cream': '#F9F6F0',
                'dark': '#1E1E1E',
                'gold': '#C9A773',
                'darkgold': '#B08D50',
                'primary': '#2D2B30',
                'primary-foreground': '#FFFFFF',
                'secondary': '#F1F5F9',
                'secondary-foreground': '#1E1E1E',
                'border': '#E4E9F0',
                'input-border': '#D3D8E1',
                'error': '#E53E3E',
                'success': '#38A169',
                
            },
            boxShadow: {
                'input': '0 1px 2px rgba(0, 0, 0, 0.05)',
                'card': '0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
            }
        }
    }
}