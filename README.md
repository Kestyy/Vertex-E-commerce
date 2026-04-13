# TechGadget E-commerce Template

A modern, responsive e-commerce website template designed for tech gadgets and electronics. This template provides a complete shopping experience with product listings, detailed product pages, and interactive features.

## 🚀 Features

### Homepage
- **Hero Section** with call-to-action buttons
- **Product Categories** showcase (Laptops, Keyboards, Headphones, Mice)
- **Featured Products** grid with ratings and pricing
- **Deal of the Day** section with countdown timer
- **New Arrivals** showcase
- **Features Section** highlighting benefits
- **Newsletter Subscription** form
- **Responsive Footer** with links and social media

### Product Details Page
- **Product Image Gallery** with thumbnails
- **Product Information** with ratings and reviews
- **Quantity Selector** with add/remove buttons
- **Add to Cart** and **Wishlist** functionality
- **Tabbed Content** (Description, Specifications, Reviews)
- **Related Products** suggestions
- **Breadcrumb Navigation**

### Interactive Features
- **Shopping Cart** with item counter
- **Wishlist** functionality
- **Product Image Zoom** and gallery
- **Countdown Timer** for deals
- **Tab Navigation** for product details
- **Responsive Design** for all devices

## 🛠️ Technologies Used

- **HTML5** - Semantic markup
- **CSS3** - Modern styling with CSS Grid and Flexbox
- **JavaScript (ES6+)** - Interactive functionality
- **Bootstrap 5.3.0** - Responsive framework
- **Font Awesome 6.4.0** - Icons
- **Unsplash Images** - High-quality product images

## 📁 Project Structure

```
ecomerce-template/
├── index.html              # Homepage
├── details.html            # Product details page
├── README.md              # Project documentation
└── assets/
    ├── css/
    │   ├── style.css      # Homepage styles
    │   ├── details.css    # Product page styles
    │   └── partials/
    │       ├── reset.css      # CSS reset and variables
    │       ├── navbar.css     # Navigation styles
    │       └── media-query.css # Responsive breakpoints
    └── js/
        ├── script.js      # Additional scripts (currently empty)
        └── functions.js   # Core JavaScript functionality
```

## 🎨 Design System

### Color Palette
- **Primary**: `#2563eb` (Blue)
- **Primary Dark**: `#1d4ed8`
- **Secondary**: `#f3f4f6` (Light Gray)
- **Dark**: `#1f2937`
- **Success**: `#10b981` (Green)
- **Danger**: `#ef4444` (Red)
- **Warning**: `#f59e0b` (Orange)

### Typography
- **Font Family**: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif
- **Responsive font sizes** for optimal readability

## 📱 Responsive Design

The template is fully responsive with breakpoints for:
- **Desktop**: 1200px and above
- **Tablet**: 768px - 1199px
- **Mobile**: 576px - 767px
- **Small Mobile**: 575px and below

## ⚡ JavaScript Features

### Core Functions
- **updateCountdown()** - Deal timer functionality
- **addToCart()** - Shopping cart management
- **initializeTab()** - Tab navigation system
- **cartButton()** - Single product cart functionality
- **addToWishList()** - Wishlist toggle
- **imageSelector()** - Product image gallery
- **quantitySelector()** - Quantity input controls

### Interactive Elements
- Cart counter with animation
- Image gallery with thumbnail navigation
- Countdown timer for promotions
- Tab switching for product information
- Quantity adjustment controls
- Wishlist heart icon toggle

## 🚀 Getting Started

### Prerequisites
- A modern web browser
- Basic knowledge of HTML, CSS, and JavaScript

### Installation

1. **Clone or download** the repository:
   ```bash
   git clone [repository-url]
   cd ecomerce-template
   ```

2. **Open in browser**:
   - Open `index.html` in your preferred web browser
   - Or use a local server for development

3. **Development Server** (optional):
   ```bash
   # Using Python
   python -m http.server 8000
   
   # Using Node.js
   npx http-server
   
   # Using PHP
   php -S localhost:8000
   ```

## 🛠️ Customization

### Modifying Products
1. **Images**: Replace Unsplash URLs with your product images
2. **Content**: Update product names, descriptions, and prices in HTML files
3. **Styling**: Modify CSS variables in `assets/css/partials/reset.css`

### Adding New Features
1. **New Pages**: Create additional HTML files following the existing structure
2. **Styling**: Add custom CSS to respective files
3. **Functionality**: Extend `functions.js` with new interactive features

### Color Scheme
Update the CSS custom properties in `reset.css`:
```css
:root {
    --primary: #your-color;
    --secondary: #your-color;
    /* ... other variables */
}
```

## 📋 Browser Support

- **Chrome** 90+
- **Firefox** 88+
- **Safari** 14+
- **Edge** 90+
- **Mobile browsers** (iOS Safari, Chrome Mobile)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature/new-feature`)
5. Create a Pull Request

## 📝 License

This project is open source and available under the [MIT License](LICENSE).

## 🙏 Acknowledgments

- **Bootstrap** for the responsive framework
- **Font Awesome** for the icon library
- **Unsplash** for high-quality product images
- **AI assistance** in initial code generation and refactoring

## 📞 Support

For questions, suggestions, or issues, please:
1. Check existing issues in the repository
2. Create a new issue with detailed description
3. Contact the maintainer

---

**Built with ❤️ for the web development community**
