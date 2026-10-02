Artificial Intelligence Integration Using ElevenLabs

Documentation


By:

Garcia, Gian Neilvin R.
Gutierrez, Eugene Timothy C.
Mitra, Chriz Kelly F.


September 2026

Course context: IPT102 - Integrative Programming and Technologies 2
Project: LipaByte - Peer-to-Peer Tech Device Rental Web App
Agent name: LipaByte Assistant
Institution: University of Batangas - Lipa City (BSIT)

--------------------------------------------------------------------------------

1. Purpose of the AI Assistant

The LipaByte Assistant is a dedicated product-support Retrieval-Augmented Generation (RAG) chatbot designed for the LipaByte web platform. Its main objective is to address common user queries regarding system functionality, including:

- Understanding the core concept and purpose of LipaByte
- Navigating account registration and login processes
- Listing or borrowing tech devices
- Determining availability constraints for in-app messaging
- Understanding conditions for submitting reviews and ratings
- Using Device Inquiries (Firestore-backed support requests)
- Navigating Marketplace, My Activity, and related pages
- Quoting listing names, daily/weekly rates, locations, and availability when those details appear in the curated listings snapshot knowledge file

The LipaByte Assistant is not a replacement for the in-app messaging feature between renters and owners. While in-app messaging handles operational coordination for specific rental transactions, the Assistant functions strictly as an educational user guide for platform navigation, feature explanation, and snapshot-based catalog guidance.

1.1 Design Goals

- Grounded Answers: Utilize a curated, domain-specific knowledge base (RAG) rather than unverified general generation.
- Multimodal Interaction: Provide conversational voice and text interactions powered by ElevenLabs.
- Continuous Accessibility: Remain available across the live website as a persistent floating widget.
- Factuality and Honesty: Explicitly acknowledge information gaps rather than generating fabricated details (hallucinations).
- Catalog Awareness (Snapshot): Answer price/listing questions only from an exported marketplace snapshot-never by inventing rates or querying private live user data.

--------------------------------------------------------------------------------

2. System Overview: LipaByte

LipaByte is a custom PHP + MySQL web application tailored for tertiary students in Lipa and Batangas. The platform enables users to:

- Register using verified institutional (.edu.ph) email addresses.
- Browse the Marketplace for available tech equipment.
- List personal electronic devices for rent.
- Submit rental requests through a structured workflow (Pending -> Approved -> Completed).
- Engage in peer-to-peer communication via the Messages module upon rental approval.
- Submit star ratings and feedback upon rental completion.
- Submit and manage Device Inquiries (help requests stored in Cloud Firestore, while core rental data remains in MySQL).

2.1 Technical Architecture (Main Application)

Architecture Component | Technology Stack
Frontend | HTML5, CSS3, JavaScript (Vanilla JS)
Backend | PHP 8
Primary Database | MySQL (managed via phpMyAdmin / InfinityFree)
Device Inquiries Store | Cloud Firestore (Firebase) - hybrid Option B
Hosting Platform | InfinityFree

The RAGbot operates as an isolated add-on module. It does not access or query the live MySQL database containing private user records, and it does not read live Firestore inquiry documents. Instead, it relies exclusively on curated reference documentation and a regenerated listings snapshot hosted within ElevenLabs.

--------------------------------------------------------------------------------

3. Conceptual Framework: Retrieval-Augmented Generation (RAG)

Retrieval-Augmented Generation (RAG) is an architectural pattern that enhances Large Language Model (LLM) outputs by incorporating domain-specific reference data into the generation pipeline.

Standard LLMs draw exclusively from pre-trained parametric memory, making them prone to factual inaccuracies (hallucinations) when queried about custom or project-specific workflows.

3.1 RAG Operational Workflow

[User Query]
     |
     v
[Retrieve Relevant Chunks from Knowledge Base]
     |
     v
[Pass Context + Prompt to LLM]
     |
     v
[Generate Grounded Output]
     |
     v
[Deliver Text and Voice Response]

Workflow Summary: Ask -> Retrieve -> Understand -> Respond

--------------------------------------------------------------------------------

4. Implementation Details

4.1 Platform Selection

The integration utilizes ElevenLabs Agents (Conversational AI). This platform provides native support for conversational agent orchestration, knowledge base RAG execution, custom voice synthesis, and web widget embedding.

4.2 Agent Configuration

Setting | Value
Agent Name | LipaByte Assistant
Base Template | Blank Agent (configured for granular instruction and knowledge control)
Primary Language | English
RAG Mode | Enabled (Every turn)
Voice Model | Clear English (e.g., Eric)
Agent ID | agent_3801m1kd9f2mfmsv9v0mstagftj2
Initial Greeting | Welcome users to ask LipaByte product questions (register, list, borrow, Messages, reviews, Inquiries, listing prices from snapshot)

4.3 Agent System Instructions

The system prompt (source: ragbot/AGENT_INSTRUCTIONS.txt) enforces strict operational guidelines:

- Role Definition: Act strictly as a product-support assistant for LipaByte.
- Knowledge Scope: Answer queries solely based on retrieved knowledge base contents.
- Target Audience Context: Tailored for university students in the Batangas/Lipa region.
- Listing Prices: Quote names, rates, locations, and availability only when present in the listings snapshot; otherwise suggest browsing Marketplace.
- Strict Constraints: Refrain from inventing financial fees, policies, or private user details. Never claim administrative capabilities (e.g., approving rentals, logging into accounts, or creating inquiries for the user).
- Output Standards: Deliver concise, direct answers. Formally state when requested information is unavailable.

4.4 Knowledge Base Structure

The knowledge base comprises structured plain text files located under ragbot/knowledge/:

File | Contents
01-what-is-lipabyte.txt | Platform overview, scope, assistant boundaries, and development team details
02-accounts-and-registration.txt | Account creation, login protocols, and email verification rules
03-marketplace-and-listings.txt | Device browsing and listing workflow
04-borrowing-workflow.txt | Transaction request lifecycle and completion stages
05-messages-and-chat.txt | Functional scope and activation triggers for the chat module
06-reviews-and-ratings.txt | Evaluation metrics and review submission criteria
07-faq.txt | Frequently Asked Questions (FAQ) reference pairings
08-device-inquiries.txt | Device Inquiries feature (how to submit, track, and how it differs from Messages)
09-navigation-and-activity.txt | Site navigation and My Activity guidance
10-listings-snapshot-guide.txt | Rules for using the listings snapshot (no invented prices; refresh process)
11-listings-snapshot.txt | Generated catalog snapshot: listing ID, name, category, daily/weekly rates, location, status, condition

Technical Insight: During initial deployment, RAG settings were set to Disabled, resulting in "No matching chunks found" errors. Updating the setting to Enable RAG = Every turn resolved the issue, enabling accurate chunk retrieval and low-distance vector search matches.

4.5 Listings Snapshot Export (Catalog Coverage)

Because the assistant must not query live private databases, marketplace prices are provided through a controlled export:

c:\xampp\php\php.exe c:\xampp\htdocs\lipabyte\tools\export_listings_snapshot.php
c:\xampp\php\php.exe c:\xampp\htdocs\lipabyte\tools\export_listings_snapshot.php --live

- Default export uses the local XAMPP MySQL config.
- --live uses InfinityFree credentials from config/database.local.php.
- Output file: ragbot/knowledge/11-listings-snapshot.txt
- After export, the file must be re-uploaded to ElevenLabs Knowledge Base and the agent Published.
- Website hard-refresh alone does not update knowledge; only ElevenLabs upload + publish does.
- Re-export whenever listings are added, removed, or prices/availability change.

4.6 Voice and Interaction Modalities

- Voice Processing: Managed via native ElevenLabs call controls in the web widget (requires client-side microphone permission).
- Text Processing: Handled through the widget text input field.
- Resource Allocation: ElevenLabs tier limits apply; demonstrations should be kept concise to optimize usage quotas.

--------------------------------------------------------------------------------

5. System Architecture and Information Flow

5.1 High-Level Integration Model

[LipaByte Web Pages]
        |
        |  footer.php / static pages load
        |  ragbot-config.js + ragbot-widget.js
        v
[ElevenLabs ConvAI Widget]
        |
        v
[ElevenLabs Agent + RAG]
        |
        |-- Knowledge: 01-10 product docs
        |-- Knowledge: 11-listings-snapshot.txt
        |
        v
[Grounded text and voice reply to user]

Core rental data stays in MySQL. Device Inquiries data stays in Firestore. The Assistant never receives those live stores-only uploaded KB files.

5.2 Transaction Execution Sequence

1. The user navigates to any LipaByte webpage containing the floating widget.
2. The user submits a text or voice prompt.
3. The ElevenLabs backend queries the uploaded knowledge base to retrieve relevant vector text chunks.
4. The LLM synthesizes an accurate response bounded by the retrieved context.
5. The response is rendered via text display and voice output.
6. If no relevant chunks match the query, the assistant safely declines to answer.

5.3 Modular Separation

Module | Role
Transactional Messaging (messages.php) | Private communication between matched borrowers and owners for active rentals
Device Inquiries (inquiries.php + Firestore) | Support tickets for help with borrowing, listing, accounts, or technical issues
Support Assistant (RAGbot) | Public-facing, automated guidance tool accessible across site pages

--------------------------------------------------------------------------------

6. Integration and Deployment Details

6.1 JavaScript Configuration (assets/js/ragbot-config.js)

window.LIPABYTE_RAGBOT = {
  agentId: 'agent_3801m1kd9f2mfmsv9v0mstagftj2',
};

6.2 Universal Widget Loader (assets/js/ragbot-widget.js)

The loader script retrieves the configured agentId, instantiates the elevenlabs-convai custom element, loads the official ElevenLabs embed library, prevents duplicate scripts on identical pages, and degrades gracefully if no Agent ID is supplied.

6.3 Page Embed Locations

- Dynamic Pages: Integrated via includes/footer.php across application pages (e.g., home.php, list.php, messages.php, dashboard.php, inquiries.php).
- Static Pages: Embedded directly in index.html, about.html, and assistant.html.

6.4 Site Navigation Integration

An Assistant entry link is integrated into both the primary landing navigation bar (index.html, about.html) and the authenticated application header (includes/header.php). An Inquiries link is also present in the authenticated header. The assistant.html page serves as a dedicated support hub explaining the RAG workflow.

6.5 Hosting and Server Considerations (InfinityFree)

- Base URL: https://lipabytewebapp.freedev.app/lipabyte/
- Root Handler: A root redirection script at htdocs/index.html forwards incoming traffic into /lipabyte/.
- CORS / Security: The ElevenLabs Agent Security Allowlist includes lipabytewebapp.freedev.app.

6.6 Knowledge Upload Checklist

See also ragbot/UPLOAD_KB_INSTRUCTIONS.md.

1. Keep Enable RAG = Every turn.
2. Upload/replace knowledge files 01-11 in Knowledge Base -> Sources.
3. Wait until all files finish processing.
4. Paste updated AGENT_INSTRUCTIONS.txt into the agent system prompt.
5. Publish the agent.
6. Test in ElevenLabs, then hard-refresh the live website widget.

--------------------------------------------------------------------------------

7. Documentation Screenshots Placeholder

Insert screenshots / evidence here for submission:

1. ElevenLabs Agent Settings: Agent name, initial greeting, and system prompt.
2. Knowledge Base Sources: Verification view of the uploaded text documents (01-11, including 11-listings-snapshot.txt).
3. RAG Configuration: Configuration screen showing Enable RAG = Every turn.
4. Deployment Confirmation: Publish confirmation screen from ElevenLabs.
5. Live Site Integration: Marketplace (or any app page) with active floating widget.
6. Text Output Example: Valid factual product response (e.g., "What is LipaByte?").
7. Listing Price Example: Response quoting Blue Yeti / other rates from the snapshot.
8. Voice Interface UI: Call control view within the frontend widget.
9. Out-of-Scope Refusal: Safe fallback response when information is missing.
10. Dedicated Support Page: Overview of assistant.html.
11. Device Inquiries (optional): Live Inquiries page showing Firestore-backed CRUD (related hybrid feature explained by the Assistant).

Source Code - assistant.html

The complete dedicated support page source is stored in the project file assistant.html (project root). It loads assets/js/ragbot-config.js and assets/js/ragbot-widget.js, displays a short RAG workflow explanation, and hosts the ElevenLabs floating widget for voice and text questions.

--------------------------------------------------------------------------------

8. Test Cases and Validation Log

No. | Test Question Prompt | Expected System Output | Pass/Fail
1 | What is LipaByte? | Peer-to-peer tech rental platform for Lipa/Batangas students. | Pass
2 | Who is LipaByte for? | Students who need or want to lend project equipment. | Pass
3 | Do I need a school email to register? | Yes, an official institutional / .edu.ph domain email is required. | Pass
4 | How do I list a device? | Log in -> Navigate to List -> Provide details and photos -> Publish. | Pass
5 | How do I borrow a device? | Browse Marketplace -> Select item -> Set dates -> Submit request. | Pass
6 | What happens when a borrow request is approved? | In-app Messaging opens; notifications are sent; summary is displayed. | Pass
7 | When can I use Messages? | Only after a request reaches Approved or Completed status. | Pass
8 | Does LipaByte deliver the device? | No; users must coordinate pickup and return independently. | Pass
9 | When can I leave a review? | Only after the rental status is marked as Completed. | Pass
10 | Who developed LipaByte? | Garcia, Gutierrez, and Mitra (UB Lipa, BSIT). | Pass
11 | Who will be the UB president in 2040? | Formally state that the information is outside the knowledge base. | Pass
12 | What is the LipaByte bank account number? | Refuse request / state information is unavailable. | Pass
13 | How do Device Inquiries work? | Explain Inquiries page, categories, status tracking; distinguish from Messages. | Pass
14 | How much is a Blue Yeti Mic per day? | Quote daily rate(s) from listings snapshot (e.g., PHP 85.00/day range); suggest Marketplace for live checks. | Pass
15 | Can you approve my rental for me? | Refuse; assistant cannot perform account/rental actions. | Pass

--------------------------------------------------------------------------------

9. Issues Encountered and Resolutions

Issue 1: Test RAG returned no matching chunks.
Root Cause: RAG module default status was Disabled.
Resolution: Set Enable RAG option to Every turn.

Issue 2: Health check missing index file error.
Root Cause: Web app assets were nested strictly inside htdocs/lipabyte/.
Resolution: Created a root redirect file at htdocs/index.html targeting /lipabyte/.

Issue 3: Marketplace displays fallback color icons.
Root Cause: Seed database records utilize category placeholders.
Resolution: Independent of RAG module; resolved by uploading real product images.

Issue 4: Assistant widget rendered only on dedicated page.
Root Cause: Script inclusion was originally limited to assistant.html.
Resolution: Moved loader execution script into shared footer.php and static footers.

Issue 5: Website refresh did not update listing prices in the Assistant.
Root Cause: Listings snapshot and agent knowledge live on ElevenLabs; hard-refresh only reloads the widget embed.
Resolution: Upload 11-listings-snapshot.txt, update system instructions, wait for processing, Publish agent, then retest on the live site.

Issue 6: Assistant refused price questions before snapshot upload.
Root Cause: Knowledge base contained product how-to docs only; no catalog rates.
Resolution: Added export tool + 11-listings-snapshot.txt, expanded FAQ/instructions, and re-uploaded knowledge sources.

--------------------------------------------------------------------------------

10. Responsible AI Considerations

- Data Privacy: Knowledge base files contain product documentation and a public-style listings snapshot (item fields only). Private student passwords, chat contents, and personal inquiry bodies are excluded.
- Hallucination Prevention: Explicit instructions mandate that the agent must not fabricate policies, fees, or system capabilities, and must not invent listing prices absent from the snapshot.
- Boundary Validation: Out-of-knowledge queries are periodically tested to ensure proper refusal behavior.
- Human-in-the-Loop: AI serves strictly an informational role; critical actions (account registration, listing creation, rental approvals, inquiry submission) remain under human control via standard application logic.
- Snapshot Freshness: Catalog answers reflect the last exported/uploaded snapshot, not real-time MySQL. Operators re-export after catalog changes.

--------------------------------------------------------------------------------

11. Technical Reflection

Building this system demonstrated that integrating conversational AI requires more than uploading documentation: retrieval mechanics must be explicitly configured and validated (Enable RAG = Every turn). Expanding coverage to listing prices further showed that "live catalog awareness" for a hosted PHP/MySQL app can be achieved safely through a snapshot + re-upload pattern without granting the agent direct database credentials.

Furthermore, maintaining a strict architectural boundary between the informational AI Assistant, transaction-specific user messaging, and Firestore Device Inquiries prevents user confusion while enhancing overall platform accessibility. This implementation successfully demonstrates how a legacy PHP/MySQL application (with a hybrid Firestore inquiries module) can be augmented with modern AI capabilities without requiring backend architectural overhauls to the core rental system.

--------------------------------------------------------------------------------

12. Project File Structure Reference

lipabyte/
|-- assistant.html
|-- inquiries.php
|-- index.html
|-- about.html
|-- assets/
|   |-- js/
|       |-- ragbot-config.js
|       |-- ragbot-widget.js
|       |-- firebase-config.js
|       |-- inquiries.js
|-- includes/
|   |-- footer.php
|   |-- header.php
|-- tools/
|   |-- export_listings_snapshot.php
|-- ragbot/
    |-- AGENT_INSTRUCTIONS.txt
    |-- ELEVENLABS_SETUP.md
    |-- UPLOAD_KB_INSTRUCTIONS.md
    |-- TEST_QUESTIONS.md
    |-- DEMO_SCRIPT_6MIN.md
    |-- DOCUMENTATION.md
    |-- knowledge/
        |-- 01-what-is-lipabyte.txt
        |-- 02-accounts-and-registration.txt
        |-- 03-marketplace-and-listings.txt
        |-- 04-borrowing-workflow.txt
        |-- 05-messages-and-chat.txt
        |-- 06-reviews-and-ratings.txt
        |-- 07-faq.txt
        |-- 08-device-inquiries.txt
        |-- 09-navigation-and-activity.txt
        |-- 10-listings-snapshot-guide.txt
        |-- 11-listings-snapshot.txt

--------------------------------------------------------------------------------

13. System Access Links

- Live Application URL: https://lipabytewebapp.freedev.app/lipabyte/
- Dedicated Assistant Page: https://lipabytewebapp.freedev.app/lipabyte/assistant.html
- Device Inquiries Page: https://lipabytewebapp.freedev.app/lipabyte/inquiries.php
- ElevenLabs Agent ID: agent_3801m1kd9f2mfmsv9v0mstagftj2
