// New bill page
// cart is kept in a plain object: { productId: {id, name, price, qty, max} }
(function () {
    var cart = {};

    var searchBox = document.getElementById('searchBox');
    var catFilter = document.getElementById('catFilter');
    var productRows = document.getElementById('productRows');
    var cartRows = document.getElementById('cartRows');
    var emptyMsg = document.getElementById('emptyMsg');
    var discountInput = document.getElementById('discount');
    var gstInput = document.getElementById('gstPercent');

    function money(n) {
        return 'Rs. ' + Number(n).toFixed(2);
    }

    // products that match the search box and category
    function filteredProducts() {
        var text = searchBox.value.trim().toLowerCase();
        var cat = catFilter.value;
        return PRODUCTS.filter(function (p) {
            return p.name.toLowerCase().indexOf(text) !== -1 && (cat === '' || p.category === cat);
        });
    }

    function drawProducts() {
        productRows.innerHTML = '';
        var list = filteredProducts();

        if (list.length === 0) {
            productRows.innerHTML = '<tr><td class="text-center text-muted py-3">No product found</td></tr>';
            return;
        }

        list.forEach(function (p) {
            var tr = document.createElement('tr');
            tr.className = 'product-row';
            tr.innerHTML = '<td></td><td class="text-muted small"></td><td class="text-end"></td><td class="text-end"><span class="badge bg-light text-dark border"></span></td>';
            tr.children[0].textContent = p.name;
            tr.children[1].textContent = p.category;
            tr.children[2].textContent = money(p.price);
            tr.querySelector('.badge').textContent = p.stock + ' left';
            tr.addEventListener('click', function () { addToCart(p); });
            productRows.appendChild(tr);
        });
    }

    function addToCart(p) {
        if (cart[p.id]) {
            if (cart[p.id].qty >= p.stock) {
                alert('Only ' + p.stock + ' of ' + p.name + ' in stock.');
                return;
            }
            cart[p.id].qty++;
        } else {
            cart[p.id] = { id: p.id, name: p.name, price: p.price, qty: 1, max: p.stock };
        }
        drawCart();
    }

    function drawCart() {
        cartRows.innerHTML = '';
        var ids = Object.keys(cart);
        emptyMsg.style.display = ids.length ? 'none' : 'block';

        ids.forEach(function (id) {
            var item = cart[id];
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td></td>' +
                '<td>' + money(item.price) + '</td>' +
                '<td><input type="number" class="form-control form-control-sm cart-qty" min="1" max="' + item.max + '" value="' + item.qty + '"></td>' +
                '<td class="text-end">' + money(item.price * item.qty) + '</td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button></td>';
            tr.children[0].textContent = item.name;

            tr.querySelector('input').addEventListener('change', function (e) {
                var q = parseInt(e.target.value, 10);
                if (!q || q < 1) q = 1;
                if (q > item.max) {
                    alert('Only ' + item.max + ' in stock.');
                    q = item.max;
                }
                item.qty = q;
                drawCart();
            });
            tr.querySelector('button').addEventListener('click', function () {
                delete cart[id];
                drawCart();
            });
            cartRows.appendChild(tr);
        });

        updateTotals();
    }

    function updateTotals() {
        var subtotal = 0;
        Object.keys(cart).forEach(function (id) {
            subtotal += cart[id].price * cart[id].qty;
        });

        var discount = parseFloat(discountInput.value) || 0;
        if (discount < 0) discount = 0;
        if (discount > subtotal) discount = subtotal;

        var gstPercent = parseFloat(gstInput.value) || 0;
        var gst = (subtotal - discount) * gstPercent / 100;

        document.getElementById('subTxt').textContent = money(subtotal);
        document.getElementById('discTxt').textContent = money(discount);
        document.getElementById('gstTxt').textContent = money(gst);
        document.getElementById('totalTxt').textContent = money(subtotal - discount + gst);
    }

    // events
    searchBox.addEventListener('input', drawProducts);
    catFilter.addEventListener('change', drawProducts);

    // pressing enter in the search box adds the first match (handy with a barcode scanner)
    searchBox.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var list = filteredProducts();
            if (list.length) {
                addToCart(list[0]);
                searchBox.select();
            }
        }
    });

    discountInput.addEventListener('input', updateTotals);
    gstInput.addEventListener('input', updateTotals);

    document.getElementById('newCustLink').addEventListener('click', function (e) {
        e.preventDefault();
        var box = document.getElementById('newCustBox');
        box.classList.toggle('d-none');
        if (!box.classList.contains('d-none')) {
            box.querySelector('input').focus();
        }
    });

    document.getElementById('billForm').addEventListener('submit', function (e) {
        var ids = Object.keys(cart);
        if (ids.length === 0) {
            e.preventDefault();
            alert('Add at least one product to the bill.');
            return;
        }
        document.getElementById('cartField').value = JSON.stringify(
            ids.map(function (id) { return { id: cart[id].id, qty: cart[id].qty }; })
        );
    });

    drawProducts();
    drawCart();
})();
