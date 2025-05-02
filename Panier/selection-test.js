document.addEventListener('DOMContentLoaded', () => {
  const cartIcon = document.querySelector('.fa-cart-shopping');
  const cards = document.querySelectorAll('.card');

  let selectedProduct = null;

  // Capture product details on click
  cards.forEach(card => {
    card.addEventListener('click', () => {
      selectedProduct = {
        id: card.dataset.id,
        name: card.dataset.name,
        price: card.dataset.price,
      };
      alert(`Selected: ${selectedProduct.name}`);
    });
  });

  // Add product to cart on cart icon click
  cartIcon.addEventListener('click', () => {
    if (selectedProduct) {
      let cart = JSON.parse(localStorage.getItem('cart')) || [];

      // Check if the product is already in the cart
      const productExists = cart.some(item => item.id === selectedProduct.id);
      if (productExists) {
        alert(`${selectedProduct.name} is already in the cart!`);
        return;
      }

      // Add the product to the cart
      cart.push(selectedProduct);
      localStorage.setItem('cart', JSON.stringify(cart));
      alert(`${selectedProduct.name} added to cart!`);
    } else {
      alert('Please select a product first!');
    }
  });
});