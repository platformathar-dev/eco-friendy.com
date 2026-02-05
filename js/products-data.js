// products-data.js - بيانات المنتجات

const productsData = [
    {
        id: 1,
        name: 'طعام كلاب عضوي ممتاز',
        category: 'dogs',
        type: 'food',
        price: 35,
        originalPrice: null,
        image: 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=400',
        rating: 5,
        reviews: 127,
        badges: ['new', 'eco'],
        description: 'طعام كلاب عضوي 100% مصنوع من أجود المكونات الطبيعية'
    },
    {
        id: 2,
        name: 'رمل قطط طبيعي متكتل',
        category: 'cats',
        type: 'care',
        price: 18,
        originalPrice: 25,
        image: 'https://images.unsplash.com/photo-1425082661705-1834bfd09dca?w=400',
        rating: 4,
        reviews: 89,
        badges: ['sale'],
        description: 'رمل قطط طبيعي 100% قابل للتحلل ومتكتل للتنظيف السهل'
    },
    {
        id: 3,
        name: 'لعبة مضغ - مطاط طبيعي',
        category: 'dogs',
        type: 'accessories',
        price: 12,
        originalPrice: null,
        image: 'https://images.unsplash.com/photo-1535294435445-d7249524ef2e?w=400',
        rating: 5,
        reviews: 203,
        badges: ['eco'],
        description: 'لعبة مضغ آمنة من المطاط الطبيعي المتين'
    },
    {
        id: 4,
        name: 'طعام قطط - سمك طازج',
        category: 'cats',
        type: 'food',
        price: 28,
        originalPrice: null,
        image: 'https://images.unsplash.com/photo-1514984879728-be0aff75a6e8?w=400',
        rating: 5,
        reviews: 156,
        badges: ['new'],
        description: 'طعام قطط غني بالبروتين مصنوع من السمك الطازج'
    },
    {
        id: 5,
        name: 'طعام طيور عضوي',
        category: 'birds',
        type: 'food',
        price: 22,
        originalPrice: null,
        image: 'https://images.unsplash.com/photo-1583337130417-3346a1be7dee?w=400',
        rating: 4.5,
        reviews: 64,
        badges: ['eco'],
        description: 'خليط حبوب عضوي متوازن لجميع أنواع الطيور'
    },
    {
        id: 6,
        name: 'طوق جلدي فاخر',
        category: 'dogs',
        type: 'accessories',
        price: 15,
        originalPrice: 20,
        image: 'https://images.unsplash.com/photo-1548681528-6a5c45b66b42?w=400',
        rating: 4,
        reviews: 92,
        badges: ['sale', 'eco'],
        description: 'طوق جلدي طبيعي فاخر بتصميم عصري'
    },
    {
        id: 7,
        name: 'حوض أسماك زجاجي',
        category: 'fish',
        type: 'accessories',
        price: 65,
        originalPrice: null,
        image: 'https://images.unsplash.com/photo-1520990735809-1c3a1d5f8d5e?w=400',
        rating: 5,
        reviews: 45,
        badges: ['new'],
        description: 'حوض أسماك زجاجي عالي الجودة مع إضاءة LED'
    },
    {
        id: 8,
        name: 'قفص فاخر مع ملحقات',
        category: 'hamsters',
        type: 'accessories',
        price: 42,
        originalPrice: null,
        image: 'https://images.unsplash.com/photo-1425082661705-1834bfd09dca?w=400',
        rating: 4.5,
        reviews: 38,
        badges: ['eco'],
        description: 'قفص واسع ومريح مع جميع الملحقات الضرورية'
    },
    {
        id: 9,
        name: 'بيت قطط دافئ',
        category: 'cats',
        type: 'accessories',
        price: 32,
        originalPrice: 45,
        image: 'https://images.unsplash.com/photo-1450778869180-41d0601e046e?w=400',
        rating: 5,
        reviews: 178,
        badges: ['sale'],
        description: 'بيت قطط مبطن ودافئ بتصميم أنيق'
    },
    {
        id: 10,
        name: 'شامبو كلاب طبيعي',
        category: 'dogs',
        type: 'care',
        price: 24,
        originalPrice: null,
        image: 'https://images.unsplash.com/photo-1581888227599-779811939961?w=400',
        rating: 4.5,
        reviews: 112,
        badges: ['eco', 'new'],
        description: 'شامبو طبيعي لطيف على جلد الكلاب برائحة منعشة'
    },
    {
        id: 11,
        name: 'فرشاة تنظيف قطط',
        category: 'cats',
        type: 'care',
        price: 16,
        originalPrice: null,
        image: 'https://images.unsplash.com/photo-1568640347023-a616a30bc3bd?w=400',
        rating: 4,
        reviews: 87,
        badges: [],
        description: 'فرشاة ناعمة للعناية بفراء القطط'
    },
    {
        id: 12,
        name: 'لعبة كرة تفاعلية',
        category: 'dogs',
        type: 'accessories',
        price: 19,
        originalPrice: 25,
        image: 'https://images.unsplash.com/photo-1585664811087-47f65abbad64?w=400',
        rating: 5,
        reviews: 145,
        badges: ['sale'],
        description: 'كرة تفاعلية ذكية للعب والتدريب'
    }
];

// دالة للحصول على المنتجات حسب الفئة
function getProductsByCategory(category) {
    if (!category) return productsData;
    return productsData.filter(p => p.category === category);
}

// دالة للحصول على المنتجات حسب النوع
function getProductsByType(category, type) {
    return productsData.filter(p => p.category === category && p.type === type);
}

// دالة للحصول على منتج واحد بالمعرف
function getProductById(id) {
    return productsData.find(p => p.id === parseInt(id));
}

// دالة للبحث عن منتجات
function searchProducts(query) {
    query = query.toLowerCase();
    return productsData.filter(p => 
        p.name.toLowerCase().includes(query) || 
        p.description.toLowerCase().includes(query)
    );
}

// دالة للحصول على منتجات عشوائية
function getRandomProducts(count) {
    const shuffled = [...productsData].sort(() => 0.5 - Math.random());
    return shuffled.slice(0, count);
}