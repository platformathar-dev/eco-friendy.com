// ==================== إرسال الطلب (محسّن) ====================
async function submitOrder(e) {
    e.preventDefault();

    if (!currentUser) {
        showNotification('⚠️ يجب تسجيل الدخول أولاً', 'error');
        return;
    }

    const phone = document.getElementById('phone').value.trim();
    const city = document.getElementById('city').value.trim();
    const address = document.getElementById('address').value.trim();
    const notes = document.getElementById('notes').value.trim();

    // التحقق من السلة
    if (cart.length === 0) {
        showNotification('❌ السلة فارغة!', 'error');
        return;
    }

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري إرسال الطلب...';

    try {
        // حساب الإجمالي
        const subtotal = cart.reduce((sum, item) => sum + (parseFloat(item.price) * item.qty), 0);
        const shipping = subtotal >= 50 ? 0 : 3;
        const total = subtotal + shipping;

        // بناء بيانات الطلب
        const fullAddress = `${city} - ${address}`;
        
        // ✅ إضافة البريد الإلكتروني للطلب
        const orderData = {
            user_id: currentUser.id,
            customer_name: currentUser.fullname,
            customer_email: currentUser.email, // ✅ إضافة البريد الإلكتروني
            customer_phone: phone,
            customer_address: fullAddress,
            shipping_address: fullAddress,
            notes: notes || '',
            payment_method: selectedPayment,
            status: 'pending',
            items: cart.map(item => ({
                product_id: parseInt(item.id),
                quantity: parseInt(item.qty),
                price: parseFloat(item.price)
            })),
            total_amount: parseFloat(total.toFixed(2))
        };

        console.log('📤 إرسال البيانات:', orderData);

        // إرسال الطلب
        const response = await fetch('api/place-order.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(orderData)
        });

        console.log('📥 حالة الاستجابة:', response.status);

        // قراءة النص أولاً
        const responseText = await response.text();
        console.log('📄 نص الاستجابة:', responseText);

        // محاولة تحويل النص إلى JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('❌ خطأ في تحويل JSON:', jsonError);
            throw new Error('استجابة غير صحيحة من الخادم');
        }

        console.log('✅ بيانات الاستجابة:', data);

        // التحقق من النجاح
        if (data.success) {
            // حفظ المعلومات في localStorage
            localStorage.setItem('eco_user_phone_' + currentUser.id, phone);
            localStorage.setItem('eco_user_address_' + currentUser.id, fullAddress);

            // عرض رسالة النجاح
            showSuccessModal(currentUser.fullname, data.order_id || data.order_number, selectedPayment, data.email_sent);
            
            // تفريغ السلة
            localStorage.removeItem('eco_cart');
            cart = [];
        } else {
            throw new Error(data.message || 'فشل في حفظ الطلب');
        }

    } catch (error) {
        console.error('❌ خطأ في إرسال الطلب:', error);
        showNotification('❌ حدث خطأ: ' + error.message, 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> تأكيد الطلب';
    }
}

// ==================== عرض رسالة النجاح (محسّن) ====================
function showSuccessModal(customerName, orderId, paymentMethod, emailSent) {
    document.getElementById('orderIdDisplay').textContent = '#' + String(orderId).padStart(5, '0');
    
    let message = `شكراً لك <strong>${customerName}</strong>!<br><br>`;
    
    // ✅ إضافة رسالة عن الإيميل
    if (emailSent) {
        message += `✅ تم إرسال تأكيد الطلب إلى بريدك الإلكتروني.<br><br>`;
    }
    
    if (paymentMethod === 'cliq') {
        message += `تم استلام طلبك وننتظر تأكيد الدفع.<br><br>`;
        message += `يرجى إرسال لقطة شاشة التحويل مع رقم الطلب إلى:<br>`;
        message += `📱 واتساب: <strong>+962790083039</strong><br>`;
        message += `📧 بريد: <strong>info@eco-friendy.com</strong>`;
    } else {
        message += `سيتم توصيل طلبك قريباً.<br>`;
        message += `سنتواصل معك لتأكيد موعد التوصيل.`;
    }

    document.getElementById('successMessage').innerHTML = message;
    document.getElementById('successModal').classList.add('active');
}
