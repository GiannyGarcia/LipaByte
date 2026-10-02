# LipaByte Assistant — One-Take Demo Script (≈ 6 minutes)

**Style:** Continuous narration while the screen demo runs in the background.  
**Speakers:** Garcia · Gutierrez · Mitra  
**Do not stop between sections.** Hand off smoothly. Keep clicking/typing quietly while someone else talks.

---

## Before you hit record
Open the live Marketplace with the Assistant widget visible. Have these ready to type/ask without announcing every click:  
1) What is LipaByte?  
2) How do I borrow a device?  
3) When can I leave a review? *(voice)*  
4) Who will be the University of Batangas president in 2040?

Garcia drives the screen the whole time. Gutierrez and Mitra narrate over it.

---

## FULL SCRIPT (read in order, one take)

**Mitra:**  
Hi everyone. We are BSIT students from the University of Batangas Lipa City. I’m Chriz Kelly Mitra, together with Gian Neilvin Garcia and Eugene Timothy Gutierrez. For our IPT102 Week 3 activity, we built an AI Assistant Module for our web project called LipaByte.

**Garcia:**  
LipaByte is a peer-to-peer tech rental platform for students in Lipa and Batangas. On the site, students can browse devices, list their own gear, send borrow requests, message after approval, and leave reviews when a rental is completed. What you’re seeing now is our live website, and on the side is our LipaByte Assistant — a floating chatbot we integrated using ElevenLabs.

**Gutierrez:**  
For this week, the requirement was to create a RAGbot, not just a regular chatbot. RAG means Retrieval-Augmented Generation. So instead of inventing answers, our assistant first retrieves information from a knowledge base we prepared about LipaByte, then generates a response from that. While we talk, we’ll show text chat, a short voice question, and one question that is purposely not in the knowledge base.

**Garcia:**  
We’ll start with a simple text question: “What is LipaByte?”  
*(types and sends; keep talking through the wait if needed)*  
As the assistant replies, you can see it explains that LipaByte is a student tech rental marketplace for Lipa and Batangas. That matches the product overview in our knowledge documents.

**Gutierrez:**  
Next, we ask something more practical: “How do I borrow a device?”  
*(Garcia types/sends while Gutierrez continues)*  
The assistant should walk through the real flow of our system — browse the Marketplace, open a listing, choose dates, send a borrow request, and wait for the owner to approve. This shows the bot is useful as a product guide, not just a general AI chat.

**Mitra:**  
Now we’ll try voice. Garcia will start a call in the widget and ask, “When can I leave a review?”  
*(Garcia clicks call, asks the question)*  
Reviews in LipaByte only unlock after a rental is marked Completed, and both the renter and the owner can leave feedback. If the assistant answers along those lines, that means the knowledge base and voice conversation are working together.

**Gutierrez:**  
Finally, we test responsible behavior with a question outside our documents: “Who will be the University of Batangas president in 2040?”  
*(Garcia sends it)*  
A good RAGbot should not invent an answer. It should say the information is not available in its knowledge base. That was one of the key points in our Week 3 activity — AI should stay grounded and admit what it doesn’t know.

**Mitra:**  
So how did we build this? We created a blank ElevenLabs agent named LipaByte Assistant, wrote clear instructions so it only answers from our files, and uploaded seven knowledge documents covering registration, marketplace, borrowing, messages, reviews, and FAQs. We also turned RAG on for every turn so each question searches that knowledge first.

**Garcia:**  
For the website side, we connected our Agent ID through a small config file and loaded the ElevenLabs widget from our site footer, so the assistant appears across pages like Marketplace, not only on one help page. It’s important to note that this Assistant is separate from LipaByte Messages. Messages is for coordination between a renter and an owner after a borrow is approved. This RAGbot is for product help.

**Gutierrez:**  
In closing, our Week 3 output is a working ElevenLabs RAGbot integrated into LipaByte — with a clear purpose, a prepared knowledge base, text and voice interaction, and website embedding. The main lesson for us is that a useful AI assistant needs good instructions, reliable knowledge, and careful testing, especially for questions it should refuse to guess.

**Mitra:**  
Thank you for watching our demonstration. This is Mitra, Garcia, and Gutierrez for LipaByte Assistant. Thank you!

---

## One-take stage directions (silent cues for Garcia)

While others speak, keep the screen moving naturally:

| When someone says… | You do… |
|--------------------|---------|
| “live website” / LipaByte intro | Show Marketplace; open the widget |
| “What is LipaByte?” | Type + send that question |
| “How do I borrow…” | Type + send that question |
| “voice” / “leave a review” | Start call → ask the review question → end call |
| “president in 2040” | Type + send the out-of-KB question |
| “how did we build” / knowledge | Briefly flash `assistant.html` or keep widget open |
| outro | Leave a clear answer on screen |

If a reply is long, don’t wait in silence — continue the next sentence and let the answer finish in the background.

---

## Target pace
- Intro through first question: ~1:30  
- Second question + voice + out-of-KB: ~2:00  
- Build + integration explanation: ~1:30  
- Closing: ~0:45  
**Total ≈ 5:30–6:00**
