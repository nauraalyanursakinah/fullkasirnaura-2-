// Fungsi untuk memuat produk ke halaman kasir
function loadProducts(categoryId = null) {
    const url = categoryId ? `../api/products.php?category_id=${categoryId}` : '../api/products.php';
    
    fetch(url)
        .then(response => response.json())
        .then(products => {
            const grid = document.getElementById('productGrid');
            grid.innerHTML = ''; // Kosongkan grid
            
            products.forEach(product => {
                const card = document.createElement('div');
                card.className = 'product-card';
                card.innerHTML = `
                    <img src="../assets/images/${product.image}" alt="${product.name}" onerror="this.src='../assets/images/default-product.jpg'">
                    <h6>${product.name}</h6>
                    <p class="mb-1">Rp ${product.price.toLocaleString('id-ID')}</p>
                    <small class="text-muted">Stok: ${product.stock}</small>
                `;
                card.onclick = () => addToCart(product.id);
                grid.appendChild(card);
            });
        })
        .catch(error => console.error('Error loading products:', error));
}

// Fungsi untuk menambah ke keranjang (via API)
function addToCart(productId) {
    fetch('../api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'add', product_id: productId, quantity: 1 })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartDisplay(data.cart);
        } else {
            alert(data.message);
        }
    });
}

// Fungsi untuk memperbarui tampilan keranjang
function updateCartDisplay(cart) {
    const cartItems = document.getElementById('cartItems');
    const cartCount = document.getElementById('cartCount');
    
    cartItems.innerHTML = '';
    let total = 0;
    let itemCount = 0;

    if (cart.length === 0) {
        cartItems.innerHTML = '<p class="text-muted">Keranjang kosong</p>';
    } else {
        cart.forEach(item => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;
            itemCount += item.quantity;

            const itemDiv = document.createElement('div');
            itemDiv.className = 'cart-item';
            itemDiv.innerHTML = `
                <div>
                    <strong>${item.name}</strong><br>
                    <small>Rp ${item.price.toLocaleString('id-ID')} x ${item.quantity}</small>
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="updateQuantity(${item.id}, -1)">-</button>
                    <span class="mx-2">${item.quantity}</span>
                    <button class="btn btn-sm btn-outline-secondary" onclick="updateQuantity(${item.id}, 1)">+</button>
                    <button class="btn btn-sm btn-danger ms-2" onclick="removeFromCart(${item.id})"><i class="fas fa-trash"></i></button>
                </div>
            `;
            cartItems.appendChild(itemDiv);
        });
    }

    if(cartCount) cartCount.textContent = itemCount;
    document.getElementById('cartTotal').textContent = `Rp ${total.toLocaleString('id-ID')}`;
    calculateChange(); // Hitung ulang kembalian
}

// Fungsi untuk memperbarui quantity
function updateQuantity(productId, change) {
    // Cari item di cart yang ada di tampilan
    const cartItems = document.querySelectorAll('#cartItems .cart-item');
    let currentQuantity = 0;
    cartItems.forEach(item => {
        if (item.innerHTML.includes(`updateQuantity(${productId},`)) {
            const quantityText = item.querySelector('span').textContent;
            currentQuantity = parseInt(quantityText);
        }
    });

    const newQuantity = currentQuantity + change;
    
    if (newQuantity > 0) {
        fetch('../api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update', product_id: productId, quantity: newQuantity })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartDisplay(data.cart);
            }
        });
    } else {
        removeFromCart(productId);
    }
}

// Fungsi untuk menghapus dari keranjang
function removeFromCart(productId) {
    // Cara sederhana: set quantity ke 0, yang akan memicu penghapusan
    updateQuantity(productId, -999); // Angka besar untuk memastikan dihapus
}

// Fungsi untuk mengosongkan keranjang
function clearCart() {
    if (confirm('Yakin ingin mengosongkan keranjang?')) {
        fetch('../api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'clear' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartDisplay([]);
            }
        });
    }
}

// Fungsi untuk toggle input uang tunai
function toggleCashInput() {
    const method = document.getElementById('paymentMethod').value;
    const cashDiv = document.getElementById('cashAmountDiv');
    if (method === 'cash') {
        cashDiv.style.display = 'block';
    } else {
        cashDiv.style.display = 'none';
        document.getElementById('changeAmountDiv').style.display = 'none';
    }
    calculateChange();
}

// Fungsi untuk menghitung kembalian
function calculateChange() {
    const method = document.getElementById('paymentMethod').value;
    const totalText = document.getElementById('cartTotal').textContent;
    const total = parseInt(totalText.replace(/[^\d]/g, '')) || 0;
    const cashAmount = parseInt(document.getElementById('cashAmount').value) || 0;
    
    if (method === 'cash' && cashAmount >= total) {
        const change = cashAmount - total;
        document.getElementById('changeAmount').textContent = `Rp ${change.toLocaleString('id-ID')}`;
        document.getElementById('changeAmountDiv').style.display = 'block';
    } else {
        document.getElementById('changeAmountDiv').style.display = 'none';
    }
}

// Event listener untuk input uang tunai
document.getElementById('cashAmount').addEventListener('input', calculateChange);

// Fungsi untuk memproses checkout
function processCheckout() {
    fetch('../api/cart.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success || data.cart.length === 0) {
                alert('Keranjang kosong!');
                return;
            }

            const paymentMethod = document.getElementById('paymentMethod').value;
            const cashAmount = parseInt(document.getElementById('cashAmount').value) || 0;
            const totalPrice = data.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

            if (paymentMethod === 'cash' && cashAmount < totalPrice) {
                alert('Uang tunai tidak mencukupi!');
                return;
            }

            const orderData = {
                items: data.cart,
                payment_method: paymentMethod,
                cash_amount: cashAmount
            };

            fetch('../api/transactions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Pembayaran berhasil! Transaksi #' + result.transaction_id);
                    // Refresh tampilan keranjang
                    updateCartDisplay([]);
                    document.getElementById('cashAmount').value = '';
                    document.getElementById('changeAmountDiv').style.display = 'none';
                    // Bisa ditambahkan logika print receipt di sini
                    // window.open(`receipt.php?id=${result.transaction_id}`, '_blank');
                } else {
                    alert('Terjadi kesalahan: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Gagal memproses transaksi.');
            });
        });
}

// Jalankan saat halaman kasir dimuat
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('productGrid')) {
        loadProducts();
        // Muat cart dari session saat halaman dibuka
        fetch('../api/cart.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartDisplay(data.cart);
                }
            });
    }
});