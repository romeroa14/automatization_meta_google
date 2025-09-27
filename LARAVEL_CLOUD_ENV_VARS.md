# 🌐 Variables de Entorno para Laravel Cloud

## 📋 **CONFIGURACIÓN EN LARAVEL CLOUD:**

### **Variables de Instagram Chatbot:**

```env
INSTAGRAM_ACCESS_TOKEN=IGAASTMZAj71HVBZAFFpUmpmZAGRiLTFPdFcxaEEyanBkVVlranQ4VXg4UEFfRmdWdy1rNURzdlVYcVNPY0Fld1dqMF9RZAWhJQ1pTaXhWZA2d3cEJnM1BoZAkVEQVFCSTdyMG5pLTdYd2lZAQmNKQTJlcEdxaW4xOVJvSWNOaGFCOVFoMAZDZD
INSTAGRAM_VERIFY_TOKEN=adsbot
INSTAGRAM_APP_SECRET=e532fabf36032fe89fed6769f1b82999
INSTAGRAM_APP_ID=1287741136163957
```

### **🔧 Cómo Configurar en Laravel Cloud:**

1. **Ve a tu proyecto** en Laravel Cloud
2. **Settings > Environment Variables**
3. **Agrega cada variable** una por una:

#### **Variable 1:**
- **Key:** `INSTAGRAM_ACCESS_TOKEN`
- **Value:** `IGAASTMZAj71HVBZAFFpUmpmZAGRiLTFPdFcxaEEyanBkVVlranQ4VXg4UEFfRmdWdy1rNURzdlVYcVNPY0Fld1dqMF9RZAWhJQ1pTaXhWZA2d3cEJnM1BoZAkVEQVFCSTdyMG5pLTdYd2lZAQmNKQTJlcEdxaW4xOVJvSWNOaGFCOVFoMAZDZD`

#### **Variable 2:**
- **Key:** `INSTAGRAM_VERIFY_TOKEN`
- **Value:** `adsbot`

#### **Variable 3:**
- **Key:** `INSTAGRAM_APP_SECRET`
- **Value:** `e532fabf36032fe89fed6769f1b82999`

#### **Variable 4:**
- **Key:** `INSTAGRAM_APP_ID`
- **Value:** `1287741136163957`

### **🔗 URLs para Meta:**

- **Webhook URL:** `https://admetricas.com/webhook/instagram`
- **Verification Token:** `adsbot`

### **🧪 Comandos de Testing:**

#### **Verificar Webhook:**
```bash
curl -X GET "https://admetricas.com/webhook/instagram?hub_mode=subscribe&hub_verify_token=adsbot&hub_challenge=test123"
```

#### **Probar Mensaje:**
```bash
curl -X POST "https://admetricas.com/webhook/instagram" \
  -H "Content-Type: application/json" \
  -d '{
    "entry": [{
      "messaging": [{
        "sender": {"id": "123456789"},
        "message": {"text": "Hola, quiero información sobre planes"}
      }]
    }]
  }'
```

### **📱 Configuración en Meta for Developers:**

1. **Ve a:** https://developers.facebook.com/
2. **Selecciona tu app:** ID `1287741136163957`
3. **Webhooks > Instagram**
4. **Configurar:**
   - **Callback URL:** `https://admetricas.com/webhook/instagram`
   - **Verify Token:** `adsbot`
   - **Suscribirse a:** `messages`

### **⏰ Token de 60 Días:**

- **Token actual:** Válido por 60 días
- **Renovación:** Necesaria antes del vencimiento
- **Monitoreo:** Revisar logs para errores de token

### **🚨 Troubleshooting:**

#### **Error 403:**
- ✅ Verificar `INSTAGRAM_VERIFY_TOKEN=adsbot`
- ✅ Comprobar URL del webhook
- ✅ Verificar que la app esté activa

#### **Error 500:**
- ✅ Revisar logs en Laravel Cloud
- ✅ Verificar tokens de acceso
- ✅ Comprobar conexión a BD

#### **No responde:**
- ✅ Verificar `INSTAGRAM_ACCESS_TOKEN`
- ✅ Comprobar permisos de la app
- ✅ Revisar configuración de webhook

### **📊 Monitoreo:**

#### **Logs importantes:**
- Mensajes recibidos
- Respuestas enviadas
- Errores de API
- Conversaciones registradas

#### **Métricas:**
- Mensajes procesados
- Respuestas exitosas
- Conversiones a WhatsApp
- Ventas cerradas

### **📞 Soporte:**
- **Email:** info@admetricas.com
- **WhatsApp:** https://wa.me/584241234567
- **Sitio web:** https://admetricas.com