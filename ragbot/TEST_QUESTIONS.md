# LipaByte Assistant — Test Questions

Ask these in ElevenLabs **Test RAG / Test chat** and on the live website widget (`assistant.html` or any page with the floating bubble).  
Mark Pass / Fail and note the response briefly for documentation.

## In-knowledge questions (expect grounded answers)

| # | Question | Expected focus |
|---|----------|----------------|
| 1 | What is LipaByte? | Peer-to-peer tech rental for Lipa/Batangas students |
| 2 | Who can register on LipaByte? | Students with institutional / .edu.ph school email |
| 3 | How do I list a device? | Login → List → details/photos → publish |
| 4 | How do I borrow a device? | Marketplace → listing → dates → request → wait for approval |
| 5 | What happens when a borrow request is approved? | Status Approved, chat opens, notifications, transaction summary |
| 6 | When can I use Messages? | After approval (Approved/Completed rentals) |
| 7 | When can I leave a review? | After rental is Completed |
| 8 | Who reviews whom? | Renter rates owner; owner rates renter; one review each per rental |
| 9 | Does LipaByte deliver the device? | No — students coordinate pickup/return |
| 10 | Who developed LipaByte? | Gian Neilvin R. Garcia, Eugene Timothy C. Gutierrez, Chriz Kelly F. Mitra (UB Lipa, BSIT) |
| 11 | How do Device Inquiries work? | Inquiries page, categories, status tracking; not the same as Messages |
| 12 | How much is a Blue Yeti Mic per day? | Quote PHP daily rate(s) from listings snapshot |

## Out-of-knowledge questions (must NOT invent)

| # | Question | Expected behavior |
|---|----------|-------------------|
| 13 | Who will be the University of Batangas president in 2040? | Say information is not available in the knowledge base |
| 14 | What is the exact bank account number of LipaByte for payments? | Say information is not available |
| 15 | Can you approve my rental for me? | Refuse — assistant cannot perform account actions |

## Optional extra checks
16. Is the LipaByte Assistant the same as Messages? → No; Assistant = product help, Messages = rental chat  
17. Can you reset my password? → Should refuse / say it cannot  
18. How do I open My Activity? → Navigation guidance from knowledge files  

## Pass criteria
- Answers for 1–12 match the knowledge base / snapshot and stay relevant.
- Answers for 13–15 clearly refuse to invent facts or claim admin powers.
- Voice response is understandable.
- Widget works on `assistant.html` and app pages via `footer.php`.
- Price answers appear only after `11-listings-snapshot.txt` is uploaded and the agent is published.
