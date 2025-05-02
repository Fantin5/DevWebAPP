document.addEventListener('DOMContentLoaded', () => {
  const cartItemsContainer = document.getElementById('cart-items');
  const clearCartButton = document.getElementById('clear-cart');
  const trashIcon = document.querySelector('.fa-trash');

  let cart = JSON.parse(localStorage.getItem('cart')) || [];

  // Function to render the cart items
  function renderCart() {
    cartItemsContainer.innerHTML = ''; // Clear the container
    if (cart.length === 0) {
      cartItemsContainer.innerHTML = '<p>Your cart is empty.</p>';
    } else {
      cart.forEach((item, index) => {
        const itemElement = document.createElement('div');
        itemElement.textContent = `${item.name} - ${item.price}`;
        itemElement.dataset.index = index; // Add index for identification
        itemElement.classList.add('cart-item'); // Add a class for styling
        cartItemsContainer.appendChild(itemElement);
      });
    }
  }

  // Render the cart on page load
  renderCart();

  // Remove a specific item when clicking on it and then the trash icon
  let selectedItemIndex = null;

  cartItemsContainer.addEventListener('click', (event) => {
    if (event.target.classList.contains('cart-item')) {
      selectedItemIndex = event.target.dataset.index;
      alert(`Selected: ${cart[selectedItemIndex].name}`);
    }
  });

  trashIcon.addEventListener('click', () => {
    if (selectedItemIndex !== null) {
      cart.splice(selectedItemIndex, 1); // Remove the selected item
      localStorage.setItem('cart', JSON.stringify(cart)); // Update localStorage
      renderCart(); // Re-render the cart
      selectedItemIndex = null; // Reset the selected item
      alert('Item removed from the cart!');
    } else {
      alert('Please select an item to remove!');
    }
  });

  // Clear all items when clicking the "Vider le Panier" button
  clearCartButton.addEventListener('click', () => {
    cart = []; // Clear the cart array
    localStorage.setItem('cart', JSON.stringify(cart)); // Update localStorage
    renderCart(); // Re-render the cart
    alert('Cart cleared!');
  });
});