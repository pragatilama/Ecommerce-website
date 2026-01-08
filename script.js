const form=document.getElementById('form'); 
const username= document.getElementById('username'); 
const email = document.getElementById('email'); 
const password = document.getElementById('password'); 
const confirm_password = document.getElementById('confirm_password'); 
const contact = document.getElementById('phone'); 
const error_messages= document.getElementById('error_messages')

// Form validation code (keeping your existing code)
if(form) {
    form.addEventListener('submit', (e) =>{           
        let errors = [];      
        if(username){         
            errors = getSignupFormErrors(username, email, password, confirm_password, contact);     
        }     
        else{         
            errors = getLoginFormErrors(email, password);     
        }      
        if(errors.length > 0){         
            e.preventDefault();         
            error_messages.innerText = errors.join(". ")     
        }  
    });
}

function getSignupFormErrors(username, email, password, confirm_password, contact){     
    let errors = [];      
    if(username.value.trim() === ""){       
        errors.push('Username is required');        
        username.parentElement.classList.add('incorrect');     
    }      
    if(email.value.trim() === ""){       
        errors.push('Email is required');        
        email.parentElement.classList.add('incorrect');     
    }      
    if(password.value.length < 8){       
        errors.push('Password must be at least 8 characters long');        
        password.parentElement.classList.add('incorrect');     
    }      
    if(password.value !== confirm_password.value){       
        errors.push('Passwords do not match');        
        confirm_password.parentElement.classList.add('incorrect');     
    }      
    if(contact.value.trim().length < 10){       
        errors.push('Contact number is not valid');        
        contact.parentElement.classList.add('incorrect');     
    }      
    return errors; 
}  

function getLoginFormErrors(email, password){     
    let errors = [];      
    if(email.value.trim() === ""){       
        errors.push('Email is required');        
        email.parentElement.classList.add('incorrect');     
    }      
    if(password.value.trim() === ""){       
        errors.push('Password is required');        
        password.parentElement.classList.add('incorrect');     
    }      
    return errors;  
}  

const allInputs = [username, email, password, confirm_password, contact].filter(input=> input!=null)  
allInputs.forEach(input =>{     
    input.addEventListener('input',()=>{         
        if(input.parentElement.classList.contains('incorrect')){             
            input.parentElement.classList.remove('incorrect');             
            error_messages.innerText = "";         
        }     
    }); 
});  

//category page filtering
// Select all filter buttons and filterable cards - FIXED SELECTOR
const filterbuttons = document.querySelectorAll(".filterable-buttons button"); 
const filterablecards = document.querySelectorAll(".filterable-cards .card");  

// Define the filterCards function 
const filterCards = e => {   
    // Remove active class from the previous button   
    document.querySelector(".filterable-buttons .active").classList.remove("active");   
    // Add active class to the clicked button   
    e.target.classList.add("active");    
    
    const filterName = e.target.dataset.name;    
    
    // Iterate over each card   
    filterablecards.forEach(card => {     
        if (filterName === "all" || card.dataset.name === filterName) {       
            card.classList.remove("hide"); // show card     
        } else {       
            card.classList.add("hide"); // hide card     
        }   
    }); 
};  

// Add click event listener to each filter button 
filterbuttons.forEach(button => button.addEventListener("click", filterCards));

// Add click functionality to category cards
const categoryCards = document.querySelectorAll(".clickable-card");
categoryCards.forEach(card => {
    card.addEventListener("click", () => {
        const category = card.dataset.category;
        if (category) {
            // Navigate to brands page with category parameter
            window.location.href = `brands.php?category=${category}`;
        }
    });
    
    // Add hover effect
    card.style.cursor = "pointer";
});


//cart functionality
const cartIcon = document.querySelector(".cart-icon");
const cart = document.querySelector(".cart");
const cartClose = document.querySelector("#cart-close");

// Cart open/close
cartIcon.addEventListener("click", () => {
    checkAuthAndLoadCart();
    cart.classList.add("active");
});

cartClose.addEventListener("click", () => cart.classList.remove("active"));

// Add to cart buttons
const addCartButtons = document.querySelectorAll(".add-to-cart");
addCartButtons.forEach(button => {
    button.addEventListener("click", event => {
        const productBox = event.target.closest(".product-card");
        addToCart(productBox);
    });
});

// Check authentication before showing cart
async function checkAuthAndLoadCart() {
    try {
        const response = await fetch('check-auth.php');
        const data = await response.json();
        
        if (data.loggedIn) {
            await loadCartFromServer();
        } else {
            alert('Please login to view your cart');
            window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
        }
    } catch (error) {
        console.error('Error checking auth:', error);
    }
}

// Load cart from server
async function loadCartFromServer() {
    try {
        console.log("Loading cart from server...");
        
        const response = await fetch('cart-api.php?action=get');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        console.log("Cart data received:", data);
        
        if (data.success) {
            renderCartFromServer(data.items);
        } else {
            console.error("Failed to load cart:", data.message);
            const cartContent = document.querySelector(".cart-content");
            cartContent.innerHTML = '<div class="cart-empty">Error loading cart: ' + data.message + '</div>';
        }
    } catch (error) {
        console.error('Error loading cart:', error);
        const cartContent = document.querySelector(".cart-content");
        cartContent.innerHTML = '<div class="cart-empty">Error loading cart. Please try again.</div>';
    }
}

// Render cart items from server
function renderCartFromServer(items) {
    const cartContent = document.querySelector(".cart-content");
    cartContent.innerHTML = '';

    if (items.length === 0) {
        cartContent.innerHTML = '<div class="cart-empty">Your cart is empty</div>';
        updateTotalPrice();
        updateCartCount();
        return;
    }

    items.forEach(item => {
        const cartBox = document.createElement("div");
        cartBox.classList.add("cart-box");
        cartBox.dataset.productId = item.product_id;
        
        cartBox.innerHTML = `
            <img src="${item.image}" class="cart-img" onerror="this.src='images/placeholder.jpg'">
            <div class="cart-detail">
                <h2 class="cart-product-title">${item.product_name}</h2>
                <span class="cart-price">$${parseFloat(item.price).toFixed(2)}</span>
                <div class="cart-quantity">
                    <button class="decrement">-</button>
                    <span class="number">${item.quantity}</span>
                    <button class="increment" ${item.quantity >= item.max_quantity ? 'disabled' : ''}>+</button>
                </div>
            </div>
            <i class="ri-delete-bin-line cart-remove"></i> 
        `;

        cartContent.appendChild(cartBox);

        // Event listeners
        cartBox.querySelector(".cart-remove").addEventListener("click", () => {
            removeFromCartServer(item.product_id);
        });

        cartBox.querySelector(".decrement").addEventListener("click", () => {
            const currentQuantity = parseInt(cartBox.querySelector(".number").textContent);
            if (currentQuantity > 1) {
                updateCartQuantityServer(item.product_id, currentQuantity - 1);
            } else {
                if (confirm('Remove this item from cart?')) {
                    removeFromCartServer(item.product_id);
                }
            }
        });

        cartBox.querySelector(".increment").addEventListener("click", () => {
            const currentQuantity = parseInt(cartBox.querySelector(".number").textContent);
            if (currentQuantity < item.max_quantity) {
                updateCartQuantityServer(item.product_id, currentQuantity + 1);
            } else {
                alert('Maximum available quantity reached');
            }
        });
    });

    updateTotalPrice();
    updateCartCount();
}

// Add to cart with server integration and stock validation
async function addToCart(productBox) {
    try {
        console.log("Adding to cart...");
        
        const authResponse = await fetch('check-auth.php');
        const authData = await authResponse.json();
        
        if (!authData.loggedIn) {
            if (confirm('You need to login to add items to cart. Redirect to login page?')) {
                window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
            }
            return;
        }

        const productId = productBox.dataset.productId;
        const maxQuantity = parseInt(productBox.dataset.maxQuantity) || 0;
        const productTitle = productBox.querySelector(".product-title").textContent;

        // Check stock availability
        if (maxQuantity <= 0) {
            alert('Sorry, this product is currently out of stock');
            return;
        }

        console.log("Product ID:", productId, "Max Quantity:", maxQuantity);

        // Add to server cart
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', 1);
        formData.append('action', 'add');

        const response = await fetch('cart-api.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        console.log("Server response:", data);

        if (data.success) {
            // Show success message without alert for better UX
            showToast('Item added to cart successfully!', 'success');
            updateCartCount();
            
            // If cart is open, reload it
            if (cart.classList.contains("active")) {
                await loadCartFromServer();
            }
        } else {
            showToast('Error: ' + data.message, 'error');
        }

    } catch (error) {
        console.error('Error adding to cart:', error);
        showToast('Failed to add item to cart. Please try again.', 'error');
    }
}

// Update cart quantity on server with stock validation
async function updateCartQuantityServer(productId, newQuantity) {
    try {
        console.log("Updating quantity:", productId, newQuantity);
        
        // First check stock availability
        const stockCheck = await fetch(`check-stock.php?product_id=${productId}`);
        const stockData = await stockCheck.json();
        
        // Calculate available stock (considering what's already in cart)
        const cartResponse = await fetch('cart-api.php?action=get');
        const cartData = await cartResponse.json();
        
        let currentCartQuantity = 0;
        if (cartData.success) {
            const currentItem = cartData.items.find(item => item.product_id == productId);
            currentCartQuantity = currentItem ? currentItem.quantity : 0;
        }
        
        const availableStock = stockData.available + currentCartQuantity;
        
        if (newQuantity > availableStock) {
            showToast(`Only ${availableStock} items available in stock`, 'error');
            await loadCartFromServer(); // Reload to sync with server
            return;
        }

        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', newQuantity);
        formData.append('action', 'update');

        const response = await fetch('cart-api.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.success) {
            await loadCartFromServer(); // Reload the entire cart
        } else {
            showToast("Error: " + data.message, 'error');
            await loadCartFromServer(); // Reload to sync with server
        }
    } catch (error) {
        console.error('Error updating quantity:', error);
        showToast('Failed to update quantity. Please try again.', 'error');
    }
}

// Remove from cart on server
async function removeFromCartServer(productId) {
    try {
        if (!confirm('Are you sure you want to remove this item from your cart?')) {
            return;
        }

        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('action', 'remove');

        const response = await fetch('cart-api.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.success) {
            showToast('Item removed from cart', 'success');
            await loadCartFromServer();
        } else {
            showToast("Error: " + data.message, 'error');
        }
    } catch (error) {
        console.error('Error removing item:', error);
        showToast('Failed to remove item. Please try again.', 'error');
    }
}

// Update total price
function updateTotalPrice() {
    const totalPriceElement = document.querySelector(".total-price");
    const cartBoxes = document.querySelectorAll(".cart-box");
    let total = 0;
    
    cartBoxes.forEach(cartBox => {
        const priceElement = cartBox.querySelector(".cart-price");
        const quantityElement = cartBox.querySelector(".number");
        const price = parseFloat(priceElement.textContent.replace("$", ""));
        const quantity = parseInt(quantityElement.textContent);
        total += price * quantity;
    });
    
    totalPriceElement.textContent = `$${total.toFixed(2)}`;
}

// Update cart count from server
async function updateCartCount() {
    try {
        const response = await fetch('cart-api.php?action=get');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            const totalItems = data.items.reduce((total, item) => total + item.quantity, 0);
            const cartItemCountBadge = document.querySelector(".cart-items-count");
            
            if (totalItems > 0) {
                cartItemCountBadge.style.visibility = "visible";
                cartItemCountBadge.textContent = totalItems;
            } else {
                cartItemCountBadge.style.visibility = "hidden";
                cartItemCountBadge.textContent = "";
            }
        }
    } catch (error) {
        console.error('Error updating cart count:', error);
    }
}

// Buy now button
const buyNowButton = document.querySelector(".btn-buy");
buyNowButton.addEventListener("click", async () => {
    try {
        const authResponse = await fetch('check-auth.php');
        const authData = await authResponse.json();
        
        if (!authData.loggedIn) {
            alert('Please login to complete your purchase');
            window.location.href = 'login.php';
            return;
        }

        const cartResponse = await fetch('cart-api.php?action=get');
        const cartData = await cartResponse.json();
        
        if (!cartData.success || cartData.items.length === 0) {
            alert("Your cart is empty. Please add items to your cart before buying");
            return;
        }

        // Check if all items are still in stock
        let allInStock = true;
        let outOfStockItems = [];
        
        for (const item of cartData.items) {
            const stockCheck = await fetch(`check-stock.php?product_id=${item.product_id}`);
            const stockData = await stockCheck.json();
            
            if (item.quantity > stockData.available) {
                allInStock = false;
                outOfStockItems.push(item.product_name);
            }
        }
        
        if (!allInStock) {
            alert(`Some items in your cart are no longer available in the requested quantities:\n${outOfStockItems.join('\n')}\n\nPlease update your cart before proceeding.`);
            await loadCartFromServer();
            return;
        }

        window.location.href = 'checkout.php';
        
    } catch (error) {
        console.error('Error during checkout:', error);
        alert('Error processing checkout. Please try again.');
    }
});

// Toast notification function
function showToast(message, type = 'info') {
    // Remove existing toasts
    const existingToasts = document.querySelectorAll('.toast');
    existingToasts.forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 4px;
        color: white;
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    
    if (type === 'success') {
        toast.style.background = '#28a745';
    } else if (type === 'error') {
        toast.style.background = '#dc3545';
    } else {
        toast.style.background = '#17a2b8';
    }
    
    document.body.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Add CSS for toast animations
const toastStyles = document.createElement('style');
toastStyles.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .toast {
        font-family: Arial, sans-serif;
        font-size: 14px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
`;
document.head.appendChild(toastStyles);

// Initialize cart count on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    
    if (cartClose) {
        cartClose.addEventListener("click", () => {
            cart.classList.remove("active");
        });
    }
});