# WhatsApp Alarm Templates — create these in Meta Business Manager

Create each template under **Meta Business Manager → WhatsApp Manager → Message Templates → Create Template**.

For every template use:

- **Category:** `Utility`
- **Language:** `English` (locale code `en` — must match `WHATSAPP_TEMPLATE_LOCALE`)
- **Header:** none
- **Footer:** *(optional)* `SALTO Battery Monitor`
- **Body variables:** three, in this exact order →
  `{{1}}` = lock / room name  ·  `{{2}}` = location  ·  `{{3}}` = date & time

The **template name** must match exactly (Meta only allows lowercase letters, numbers and underscores — these already comply). The app maps each SALTO alarm code to the template name below in `config/alarms.php`.

---

### 1. `salto_alarm_intrusion`  — SALTO code 60
```
🚨 INTRUSION ALARM — SALTO lock *{{1}}* ({{2}}) reported an intrusion alarm at {{3}}. Please investigate immediately.
```
Sample values: `Room-828` · `Room-828` · `04/07/2026 09:55`

---

### 2. `salto_alarm_tamper`  — SALTO code 61
```
🚨 TAMPER ALARM — SALTO lock *{{1}}* ({{2}}) detected tampering at {{3}}. Please check the device.
```
Sample values: `Room-828` · `Room-828` · `04/07/2026 09:55`

---

### 3. `salto_alarm_forced_opening`  — SALTO code 56
```
🚪 FORCED OPENING — SALTO lock *{{1}}* ({{2}}) was forced open at {{3}}. Please investigate.
```
Sample values: `Room-828` · `Room-828` · `04/07/2026 09:55`

---

### 4. `salto_alarm_forced_closing`  — SALTO code 58
```
🚪 FORCED CLOSING — SALTO lock *{{1}}* ({{2}}) reported a forced closing at {{3}}. Please investigate.
```
Sample values: `Room-828` · `Room-828` · `04/07/2026 09:55`

---

### 5. `salto_alarm_duress`  — SALTO code 42
```
🆘 DURESS ALARM — A duress signal was triggered at SALTO lock *{{1}}* ({{2}}) at {{3}}. Follow your security protocol.
```
Sample values: `Room-828` · `Room-828` · `04/07/2026 09:55`

---

### 6. `salto_alarm_door_left_open`  — SALTO code 62
```
⏰ DOOR LEFT OPEN — SALTO lock *{{1}}* ({{2}}) has been left open (detected {{3}}). Please secure the door.
```
Sample values: `Room-828` · `Room-828` · `04/07/2026 09:55`

---

### 7. `salto_alarm_hardware_failure`  — SALTO code 119
```
🔧 HARDWARE FAULT — SALTO lock *{{1}}* ({{2}}) reported a hardware failure at {{3}}. Maintenance required.
```
Sample values: `Room-960` · `Room-960` · `21/06/2026 15:48`

---

## Notes

- **Approval time:** Utility templates are usually approved within minutes to a few hours. Alarms will start delivering automatically once each template is approved — no code change needed.
- **Recipients & channels:** Alarm alerts use the same WhatsApp / Email / SMS recipient lists and enable-toggles as battery alerts (Settings → Monitoring & Alerts). The master switch is **"Send alarm alerts"** on that tab.
- **Email & SMS** need no templates — they use built-in wording, so those channels work immediately.
- **To rename a template or add/remove an alarm code**, edit `config/alarms.php` (`codes` map). Each entry is `EventCode => [key, label, whatsapp_template, severity]`.
- **"Cleared" events** (intrusion cleared 64, tamper cleared 67, forced ended 57/59, door-open cleared 63) are shown on the Door Events page but are **not** separately notified, to avoid alarm-clear spam. Add them to `config/alarms.php` if you want notifications for those too.
