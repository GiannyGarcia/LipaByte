# ElevenLabs Setup — LipaByte Assistant (Week 3)

Do these steps in your browser. Cursor cannot log into ElevenLabs for you.

## 1. Create / open ElevenLabs
1. Go to [elevenlabs.io](https://elevenlabs.io/) and sign in.
2. Open **Conversational AI** / **Agents**.

## 2. Create the agent
- **Name:** LipaByte Assistant
- **Purpose / topic:** Product Information (LipaByte student tech rental app)
- **First message (optional):**  
  `Hi! I'm the LipaByte Assistant. Ask me how to register, list a device, borrow gear, use Messages, or leave a review.`

## 3. Paste agent instructions
1. Open `ragbot/AGENT_INSTRUCTIONS.txt`.
2. Copy everything.
3. Paste into the agent's **System / Agent instructions** field.
4. Save.

## 4. Upload the knowledge base (RAG)
Upload **all** files from:

`ragbot/knowledge/`

1. `01-what-is-lipabyte.txt`
2. `02-accounts-and-registration.txt`
3. `03-marketplace-and-listings.txt`
4. `04-borrowing-workflow.txt`
5. `05-messages-and-chat.txt`
6. `06-reviews-and-ratings.txt`
7. `07-faq.txt`

Make sure knowledge / RAG is enabled for the agent so answers are retrieved from these files.

## 5. Configure voice
1. Choose a clear, friendly English voice.
2. Test speaking with a sample question.
3. Prefer conversational mode that supports voice (and text if available).

## 6. Make the widget embeddable
In agent settings:
1. Disable authentication for the public widget (required for simple embed).
2. Open **Share / Embed / Widget**.
3. Copy your **Agent ID** (looks like `agent_...`).

## 7. Connect the website
1. Open `assets/js/ragbot-config.js`.
2. Replace `PASTE_YOUR_ELEVENLABS_AGENT_ID_HERE` with your real Agent ID.
3. Open `assistant.html` in the browser (local XAMPP or live site).
4. Confirm the ElevenLabs widget appears and you can talk to it.

Local URL example:

`http://localhost/lipabyte/assistant.html`

## 8. Test before demo
Use `ragbot/TEST_QUESTIONS.md`.
Ask at least 10 questions, including the 2 “not in knowledge base” questions.

## Compliance checklist
- [ ] Clear agent name / role / purpose
- [ ] Knowledge base uploaded
- [ ] Instructions say: use KB only, do not invent, say when unavailable
- [ ] Voice configured and tested
- [ ] Widget embedded on `assistant.html`
- [ ] 10+ test questions completed
