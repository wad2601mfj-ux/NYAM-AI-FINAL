const { initializeApp } = require('firebase/app');
const { getFirestore, collection, getDocs } = require('firebase/firestore/lite');
const { createClient } = require('@supabase/supabase-js');

// ========== FIREBASE SETUP ==========
const firebaseConfig = {
    apiKey: "AIzaSyD7FOAqhpJNVq7J6iYBcknOqWdedCIYQvQ",
    authDomain: "kingstenderapp.firebaseapp.com",
    projectId: "kingstenderapp",
    storageBucket: "kingstenderapp.firebasestorage.app",
    messagingSenderId: "264140729392",
    appId: "1:264140729392:web:01064f3b288e653a99d1f5"
};

const app = initializeApp(firebaseConfig);
const db = getFirestore(app);

// ========== SUPABASE SETUP ==========
const supabaseUrl = 'https://zgtqdtrqlqqehjxnwjcr.supabase.co';
const supabaseKey = 'sb_publishable_9DppZtR_8CJGSrCxCt77Yg_MKpGhEpo';
const supabase = createClient(supabaseUrl, supabaseKey);

async function migrateData() {
    console.log("Starting Data Migration...");

    // 1. Migrate Products
    console.log("-> Fetching 'products' from Firebase...");
    const productsSnap = await getDocs(collection(db, 'products'));
    let productsList = [];
    productsSnap.forEach(doc => {
        const d = doc.data();
        productsList.push({
            sellerName: d.sellerName || '',
            items: d.items || [],
            category: d.category || '',
            basePrice: d.basePrice || 0,
            discount: d.discount || 0,
            imageUrl: d.imageUrl || null,
            videoUrl: d.videoUrl || null,
            timestamp: d.timestamp ? d.timestamp.toDate() : new Date()
        });
    });

    if (productsList.length > 0) {
        console.log(`Pushed ${productsList.length} products to Supabase...`);
        const { error } = await supabase.from('products').insert(productsList);
        if (error) console.error("Error inserting products:", error);
        else console.log("Success: Products migrated.");
    }

    // 2. Migrate Chats
    console.log("-> Fetching 'chats' from Firebase...");
    const chatsSnap = await getDocs(collection(db, 'chats'));
    let chatsList = [];
    chatsSnap.forEach(doc => {
        const d = doc.data();
        chatsList.push({
            buyerId: d.buyerId || '',
            sender: d.sender || '',
            sellerName: d.sellerName || null,
            message: d.message || '',
            address: d.address || null,
            rawText: d.rawText || null,
            timestamp: d.timestamp ? d.timestamp.toDate() : new Date()
        });
    });

    if (chatsList.length > 0) {
        console.log(`Pushed ${chatsList.length} chats to Supabase...`);
        const { error } = await supabase.from('chats').insert(chatsList);
        if (error) console.error("Error inserting chats:", error);
        else console.log("Success: Chats migrated.");
    }

    // 3. Migrate Orders
    console.log("-> Fetching 'orders' from Firebase...");
    const ordersSnap = await getDocs(collection(db, 'orders'));
    let ordersList = [];
    ordersSnap.forEach(doc => {
        const d = doc.data();
        ordersList.push({
            seller: d.seller || '',
            buyerName: d.buyerName || '',
            buyerPhone: d.buyerPhone || '',
            address: d.address || '',
            paymentMethod: d.paymentMethod || '',
            item: d.item || '',
            quantity: d.quantity || 1,
            itemPrice: d.itemPrice || null,
            deliveryFee: d.deliveryFee || null,
            total: d.total || 0,
            status: d.status || null,
            time: d.time ? d.time.toDate() : new Date()
        });
    });

    if (ordersList.length > 0) {
        console.log(`Pushed ${ordersList.length} orders to Supabase...`);
        const { error } = await supabase.from('orders').insert(ordersList);
        if (error) console.error("Error inserting orders:", error);
        else console.log("Success: Orders migrated.");
    }

    // 4. Migrate Buyer Offers
    console.log("-> Fetching 'buyer_offers' from Firebase...");
    const offersSnap = await getDocs(collection(db, 'buyer_offers'));
    let offersList = [];
    offersSnap.forEach(doc => {
        const d = doc.data();
        offersList.push({
            buyerId: d.buyerId || '',
            sellerName: d.sellerName || '',
            item: d.item || '',
            category: d.category || '',
            basePrice: d.basePrice || 0,
            discount: d.discount || 0,
            imageUrl: d.imageUrl || null,
            videoUrl: d.videoUrl || null,
            timestamp: d.timestamp ? d.timestamp.toDate() : new Date()
        });
    });

    if (offersList.length > 0) {
        console.log(`Pushed ${offersList.length} offers to Supabase...`);
        const { error } = await supabase.from('buyer_offers').insert(offersList);
        if (error) console.error("Error inserting buyer_offers:", error);
        else console.log("Success: Buyer Offers migrated.");
    }

    console.log("Migration Complete! 🎉");
}

migrateData();
