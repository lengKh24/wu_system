I will tell the flow outside

REG = Registrar Office

SA = Student Affair

ACC = Accounting

Score = REG who work with scoring system

Exam type:

We have 4 exam = 

1st Supplementary = Student whom failed from semester result,

2nd Supplementary = Student whom failed or missed from 1st supplementary,

Restudy = Whoever missed or failed both supplementary,



Special = Not connected with above 3, same filed like other 3 but not not connect with above 3. Overall just data Entry no import export like 1st.





1\. I have another system "URM" they will export the failed student for me

2\. I will take that excel to import in our system,

payment\_status = default can be unpaid or null (u think which one is better choose it and tell me)

we will count student ID in our system and put in field(status\_note) like this follow condition:

\+ Condition

If Student ID Count <= 3 , "ប្រឡងសង", Else if = 5, "រៀនសង", Else "ការិយាល័យសិក្សា",





Step 3  = Public page

* we will have public page for student to check their name(Like our invigilator), 

1 .but this time when they see their info they can click submit then preview first, how many subject they failed what lecturer.etc. 

2\. after click submit we will have ask them which subject they want to exam also have btn to select all subject for them.

3\. after done checked system will fill in registered\_at with datetime\_now, and then we will have another admin sidebar(in admin) for SA to check:



Step 4: SA

&#x20;flow of SA = show student but if student don't have registered\_at have validate they can't check upload image BEFORE we show modal

\- have 2 action btn 

1. Edit action = edit only image and remark
2. action Paid = 

2.1.they click paid and then upload img or pdf(don't forget compresed) , when they click paid action, they can view student detail info and have place for upload invoice and remark to that. so next time they can see the reference.

2.2 after succesful change payment\_status to Paid

+Condition

\- If payment\_status that "Null or Unpaid" and more than 60 days = Hard Deleted. (automatic or we can manually)



Step 5. REG

1. i will filter(by myself) only who paid and start doing schedule (exam schedule,class, seat, etc. we will talk about it later but tell me should we risk do it right now or later)

2\. but some student can be like this:
they failed 4 subject they supposed to pay for 4 subjects but unfortunately they don't have enough money for it so decided to exam only 2 subjects first, so it mean another 2 is failed cause they don't pay for it ( is it work with my flow that described in Step 3.2)



Step 6 = Score 

have only 1 action is enter score and remark,



Step 7 = ACC

have only 1 button to view invoice, payment\_number, payment\_note



Improvement:
I want you to improve it and re-analysis flow structure, database, ERD, DRD diagram, flow work that security of our system for me and tell me what should i do instead and tell reason why, positive and negative part. we will discuss together.



sidebar have:

wrap it in Exam 

and then wrap it in retake exam

&#x20;- Main list (For REG)

&#x20;- Verify Student (For SA)



wrap in Score

Scoring System (For Score)



wrap in Accounting

Verify Retake Exam



Question for u =

I want to talk about term (it's like category) because we have different retake exam

eg: at September we have Year 3 Semester 1 B23, at October we have Year 4 Semester 2 B22, so i want to Have button status  = Active or unactive, if active when student check when register they can see their name but if i check unactive that student in that term will not see. so my opinion before we import we should create term first and then when import successfully we will check what that students belong to which term. fields i need: Title, Start Date, End date, Remark

























Condition

\- Condition 1

If Student ID Count <= 3 , "ប្រឡងសង", Else If Student ID = 4, "ការិយាល័យសិក្សា", Else if = 5, "រៀនសង"

\- Condition 2

Have manual status payment "Paid and Unpaid" and default = "Unpaid". 

If Data that "Unpaid" and more than 30 days = Hard Deleted. (Dynamic)



1\. Term

1.1 Have button status  = Active or unactive 

1.2 Create unique link (not recommend)

September Year 3 Semester 1 B23

October Year 4 Semester 2 B22



2\. Customer VS REG

Step 1 = REG -> SA

1\. Registrar Create Group Telegram with QR 

2\. Give QR to SA



Step 2: SA -> Student

After payment successfully, SA give QR to Student to join group



Step 3: REPORT

Customer service have dataTable that filter where Registered\_at !Null

Step 4: SA -> REG

SA give hardware invoice to REG, After that REG update Status payment in System

Step 5: REG

Reg have button export who only paid.

Step 6: REG control 

roumdoul.com/retakeexam



3\. REG->ACC

ACC will receive list from REG and check with their system and give us the Invoice

