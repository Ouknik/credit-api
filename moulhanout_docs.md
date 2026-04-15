# شرح تفصيلي جدا لفكرة مشروع Mol7anout

## 1) ملخص الفكرة في سطر واحد
Mol7anout هو نظام B2B يربط بين صاحب المحل والموزع: صاحب المحل ينشر طلب شراء، عدة موزعين يرسلون عروض مفصلة، صاحب المحل يختار أفضل عرض، ثم تتم متابعة التسليم حتى الاستلام النهائي بكود تأكيد.

## 2) المشكلة التي يحلها المشروع
في السوق التقليدي، صاحب المحل غالبا يطلب عبر الهاتف/واتساب بدون توثيق واضح:
- لا توجد مقارنة دقيقة بين اسعار عدة موزعين لنفس الطلب.
- لا يوجد تتبع واضح لحالة الطلب من النشر حتى التسليم.
- تفاصيل توفر كل منتج تكون غامضة.
- يحدث هدر وقت كبير في التفاوض اليدوي.

النظام يحول العملية الى Workflow رقمي واضح وموثق وقابل للقياس.

## 3) مبدأ مهم جدا: المنتجات Public
هذا شرط اساسي في الفكرة:
- كتالوج المنتجات يكون Public (مفتوح) ويمكن قراءته بدون تسجيل دخول.
- اي مستخدم او زائر او AI Client يجب ان يقدر يستعرض المنتجات والفئات والبيانات العامة بسهولة.
- المصادقة مطلوبة فقط للعمليات الحساسة (انشاء طلب، ارسال عرض، قبول عرض، تحديث حالة التسليم).

### لماذا Public Products مهم؟
- يقلل friction في بداية الاستخدام.
- يساعد اصحاب المحلات على الاستكشاف قبل التسجيل.
- يسمح ببناء طبقات ذكية (Search/Recommendation/AI assistant) فوق الكتالوج العام.
- يعطي SEO وفهرسة افضل لو توفر Web واجهات عامة.

### ما هو Public وما هو Private؟
Public:
- قائمة المنتجات
- تفاصيل المنتج
- الفئات
- بيانات عامة غير حساسة (اسم المنتج، صورة، وصف، سعر مرجعي عام)

Private:
- الطلبات
- العروض
- بيانات العملاء والارصدة
- قبول العرض
- التوصيل والتأكيد بالPIN
- اي بيانات شخصية او مالية

## 4) الاطراف الرئيسية في النظام
1. صاحب المحل (Shop Owner)
- ينشئ الطلب
- يضيف عناصر الطلب (منتج، كمية، وحدة)
- يحدد عنوان/وقت التسليم المفضل
- ينشر الطلب
- يقارن العروض
- يقبل عرضا واحدا
- يتابع التوصيل
- يؤكد الاستلام

2. الموزع (Distributor)
- يرى الطلبات المنشورة المتاحة
- يرسل عرضا مفصلا لكل عنصر (متوفر/غير متوفر، سعر الوحدة، الكمية المتاحة)
- يضع تكلفة التوصيل ووقت التوصيل المتوقع
- بعد قبول عرضه يبدأ التنفيذ والتوصيل

3. النظام
- يدير حالات الطلب والعرض والتسليم
- يرسل اشعارات فورية
- يحافظ على النزاهة (لا يمكن قبول عرضين لنفس الطلب)
- يسجل كل الاحداث (Auditability)

## 5) الرحلة الكاملة (End-to-End Flow)

### المرحلة A: الاستكشاف
- المستخدم يتصفح المنتجات العامة (Public Catalog).
- يشاهد التفاصيل والسعر المرجعي والصور.

### المرحلة B: انشاء الطلب
- صاحب المحل يدخل شاشة انشاء طلب.
- يختار منتجات ويحدد كميات ووحدات.
- يضيف عنوان التسليم وملاحظات.
- الطلب يبدأ بحالة draft.

### المرحلة C: نشر الطلب
- عند النشر يتحول الطلب الى published او receiving_offers.
- يصبح مرئيا للموزعين المناسبين.
- يبدأ استقبال العروض.

### المرحلة D: تقديم العروض
- كل موزع يرسل عرضا item-by-item.
- لكل عنصر: متوفر؟ كم الكمية؟ سعر الوحدة؟
- النظام يحسب subtotal لكل عنصر وtotal_amount للعرض.

### المرحلة E: اختيار العرض
- صاحب المحل يرى قائمة العروض مقارنة.
- يمكن تمييز العرض الارخص (cheapest) والاسرع (fastest).
- عند القبول: عرض واحد accepted، والباقي rejected تلقائيا.
- الطلب ينتقل الى accepted ثم preparing ثم on_delivery.

### المرحلة F: التسليم والتأكيد
- الموزع يحدث حالة التسليم (تحضير -> في الطريق -> تم التسليم).
- صاحب المحل يستلم الطلب ويؤكد عبر PIN (مثلا 6 ارقام).
- الحالة النهائية delivered.

## 6) نموذج البيانات (مفاهيميا)

### Order
- id
- order_number
- shop_id
- user_id
- status
- delivery_address / lat / lng
- preferred_delivery_time
- notes
- confirmation_pin (للتسليم)
- created_at

### OrderItem
- order_id
- product_id
- quantity
- unit
- notes

### Offer
- order_id
- distributor_id
- status
- total_amount
- delivery_cost
- estimated_delivery_time
- notes

### OfferItem
- offer_id
- order_item_id
- product_id
- is_available
- unit_price
- quantity
- subtotal
- notes

### Delivery
- order_id
- distributor_id
- status
- confirmation_pin
- delivered_at

## 7) حالات العمل (State Machines)

### حالات الطلب
- draft
- published
- receiving_offers
- accepted
- preparing
- on_delivery
- delivered
- cancelled

### حالات العرض
- submitted
- accepted
- rejected
- expired

### قواعد انتقال مهمة
- draft -> published فقط بواسطة صاحب الطلب.
- قبول عرض يتم مرة واحدة فقط.
- عند قبول عرض: كل العروض الاخرى تتحول rejected.
- لا يمكن التعديل على طلب بعد حالات متقدمة (حسب سياسة النظام).

## 8) قواعد اعمال حرجة
- لا يمكن لصاحب محل قبول عرض ليس لطلبه.
- لا يمكن للموزع ارسال عرض على طلب غير منشور.
- لا يمكن تسليم طلب غير مقبول.
- PIN يجب ان يطابق قبل اغلاق عملية التسليم نهائيا.
- التحقق من الصلاحيات على مستوى route + controller معا.

## 9) واجهات API بشكل عملي

### Public endpoints (بدون Token)
- GET /api/v1/products
- GET /api/v1/products/{id}
- GET /api/v1/categories

### Authenticated endpoints
- Shop Owner:
  - GET/POST /api/v1/shop/orders
  - GET /api/v1/shop/orders/{id}
  - POST /api/v1/shop/orders/{id}/publish
  - GET /api/v1/shop/orders/{id}/offers
  - POST /api/v1/shop/orders/{id}/offers/{offerId}/accept
  - POST /api/v1/shop/orders/{id}/confirm-delivery

- Distributor:
  - GET /api/v1/distributor/orders/available
  - GET /api/v1/distributor/orders/{id}
  - POST /api/v1/distributor/offers
  - PUT /api/v1/distributor/orders/{id}/status

## 10) لماذا هذا التصميم جيد تقنيا؟
- يفصل Public Catalog عن العمليات الحساسة.
- يسهل بناء Mobile/Web/AI clients متعددة على نفس API.
- يدعم التوسع: عروض اكثر، موزعين اكثر، مدن اكثر.
- يجعل مراقبة الجودة اسهل عبر حالات واضحة وقواعد انتقال ثابتة.

## 10.1) Realtime Contract (Pusher)
- القناة الخاصة بكل متجر (صاحب محل او موزع):
  - channel: `shop.{shop_id}`
  - event: `procurement.updated`
- قناة السوق للموزعين (تنبيه طلبات منشورة جديدة):
  - channel: `market.distributors`
  - event: `procurement.market.updated`

أمثلة `event_type` المرسلة حاليا:
- `order.published`
- `offer.submitted_for_owner`
- `offer.submitted_for_distributor`
- `offer.accepted_by_shop`
- `offer.accepted_for_distributor`
- `offer.rejected_for_distributor`
- `order.delivery_status_for_owner`
- `order.delivery_status_for_distributor`
- `order.delivered_confirmed`
- `order.delivered_for_distributor`
- `market.order_published`

## 11) نسخة شرح مبسط جدا لAI اخر (Step-by-Step)
تخيل AI بسيط جدا يحتاج تعليمات واضحة:

1. اقرأ المنتجات من المسارات العامة Public فقط.

2. لا تطلب تسجيل دخول لعرض المنتجات.
3. عند انشاء طلب: احفظه draft مع عناصره.
4. عند publish: اسمح للموزعين برؤيته.
5. كل موزع يرسل عرض مفصل لكل عنصر.
6. اجمع العروض واظهر مقارنة.
7. اسمح لصاحب المحل بقبول عرض واحد فقط.
8. ارفض بقية العروض تلقائيا.
9. ابدأ مرحلة التوصيل.
10. انه العملية فقط بعد ادخال PIN صحيح.
11. اعتبر اي بيانات مالية/شخصية Private.
12. اعتبر كتالوج المنتجات Public دائما.

## 12) سيناريو مثال كامل
- محل A يحتاج: 20 كرتونة ماء + 10 صناديق عصير.
- ينشئ طلب وينشره.
- موزع X يرسل عرض: الماء متوفر بالكامل، العصير جزئي.
- موزع Y يرسل عرض: السعر اعلى قليلا لكن كل العناصر متوفرة.
- صاحب المحل يختار Y.
- الطلب ينتقل accepted -> preparing -> on_delivery.
- عند الاستلام يعطي السائق PIN.
- الطلب يصبح delivered.

## 13) كيف نقيس نجاح المشروع؟ (KPIs)
- متوسط زمن من نشر الطلب حتى اول عرض.
- نسبة الطلبات التي استقبلت اكثر من عرض.
- نسبة الطلبات المكتملة Delivered.
- متوسط فرق السعر بين اعلى وادنى عرض.
- زمن دورة الطلب الكامل.

## 14) المخاطر وكيف نقللها
- خطر عروض وهمية: نظام تقييم + سياسات + تتبع نشاط.
- خطر فشل تسليم: حالات واضحة + اشعارات + دعم.
- خطر تعارض الصلاحيات: Middleware + checks داخل Controller.
- خطر سوء البيانات: Validation قوي في Backend.

## 15) رسالة تنفيذية اخيرة
هذا المشروع ليس مجرد تطبيق طلبات؛ هو محرك سوق جملة رقمي:
- اكتشاف منتجات Public
- منافسة عروض عادلة
- تنفيذ وتسليم موثق
- قابلية توسع وتشغيل عالية

اذا التزمنا بمبدأ Public Catalog + Private Transactions، سنحصل على تجربة سهلة للبداية وقوية للتشغيل اليومي.
