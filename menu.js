const cartItems = JSON.parse(localStorage.getItem('keranjang')) || [];
const cartContainer = document.getElementById('cartItems');
cartContainer.innerHTML = '';

cartItems.forEach(item => {
  const div = document.createElement('div');
  div.classList.add('cart-item');
  div.innerHTML = `
    <img src="${item.image}" alt="${item.name}" style="width:80px; height:80px; object-fit:cover; border-radius:8px; margin-right:10px;">
    <strong>${item.name}</strong><br>
    Harga: Rp ${item.price.toLocaleString()}<br>
    Jumlah: ${item.jumlah}
  `;
  cartContainer.appendChild(div);
});
