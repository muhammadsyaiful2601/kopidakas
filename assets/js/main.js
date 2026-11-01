/**
 * Interaksi modal pemesanan pada landing page Kopi Dakas.
 */

document.addEventListener('DOMContentLoaded', function () {
    const orderModal = document.getElementById('orderModal');
    if (!orderModal) return;

    const productId = document.getElementById('modalProductId');
    const productName = document.getElementById('modalProductName');
    const productPrice = document.getElementById('modalProductPrice');
    const productStock = document.getElementById('modalProductStock');
    const productImage = document.getElementById('modalProductImage');
    const quantity = document.querySelector('input[name="quantity"]');

    function closeModal() {
        orderModal.hidden = true;
        document.body.style.overflow = '';
    }

    function openModal(button) {
        productId.value = button.dataset.productId;
        productName.value = button.dataset.productName;
        productPrice.value = 'Rp ' + Number(button.dataset.productPrice).toLocaleString('id-ID');
        productStock.value = button.dataset.productStock + ' pcs';
        productImage.src = button.dataset.productImage;
        productImage.alt = button.dataset.productName;
        quantity.max = button.dataset.productStock;
        quantity.value = 1;
        orderModal.hidden = false;
        document.body.style.overflow = 'hidden';
        quantity.focus();
    }

    document.querySelectorAll('[data-order-product]').forEach(function (button) {
        button.addEventListener('click', function () {
            openModal(button);
        });
    });

    orderModal.querySelectorAll('[data-modal-close]').forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    orderModal.addEventListener('click', function (event) {
        if (event.target === orderModal) closeModal();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !orderModal.hidden) closeModal();
    });
});
