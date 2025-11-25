import sys
import json
import fitz  # PyMuPDF
import re
import spacy
import os

# --- THIS FUNCTION IS NEW/MODIFIED ---
# Reads from a file path instead of a file stream
def extract_text_from_pdf(pdf_path):
    text = ""
    try:
        if not os.path.exists(pdf_path) or os.path.getsize(pdf_path) == 0:
            return None, f"File not found or is empty: {pdf_path}"
            
        with fitz.open(pdf_path) as doc:
            for page in doc:
                text += page.get_text()
        if not text.strip():
            return None, "No text could be extracted from the PDF."
        return text, None
    except Exception as e:
        return None, f"Error opening PDF: {e}"

# --- THIS FUNCTION IS FROM YOUR SCRIPT (with fixes) ---
def extract_info(text, nlp):
    info = {}
    doc = nlp(text)

    # ======= NAME =======
    def extract_full_name(text):
        lines = [line.strip() for line in text.split("\n") if line.strip()]
        for line in lines[:5]:
            if "@" in line or re.search(r'\d', line): continue
            words = line.split()
            if 1 < len(words) <= 4 and all(w[0].isupper() for w in words if w.isalpha()):
                return line
        return None
    info["name"] = extract_full_name(text)

    # ======= EMAIL =======
    email = re.findall(r'\S+@\S+', text)
    info["email"] = email[0] if email else None

    # ======= PHONE =======
    phone = re.findall(r'\+?\d[\d\s-]{8,}\d', text)
    info["phone"] = phone[0] if phone else None
    
    # ======= EDUCATION =======
    edu_keywords = ["B.Tech", "B.E", "M.Tech", "Bachelor", "Master", "Degree", "University", "College"]
    education_lines = [line.strip() for line in text.split("\n") if any(k in line for k in edu_keywords)]
    filtered_education = [l for l in education_lines if not any(substr in l.lower() for substr in ["coursera", "university of colorado"])]
    info["education"] = " | ".join(filtered_education) if filtered_education else "Not Mentioned"

    # ======= EXPERIENCE =======
    lines = [line.strip() for line in text.split("\n") if line.strip()]
    lower_lines = [l.lower() for l in lines]
    exp_heading_tokens = ["experience", "work experience", "professional experience", "employment"]
    def is_heading_line(line):
        s = line.strip().lower()
        if not s or len(s) > 60: return False
        for tok in exp_heading_tokens:
            if s == tok or s.startswith(tok + " ") or s.startswith(tok + ":"): return True
        return False
    has_exp_heading = any(is_heading_line(l) for l in lines)
    if has_exp_heading:
        start_idx = None
        for i, l in enumerate(lines):
            if is_heading_line(l): start_idx = i + 1; break
        exp_section = []
        stop_tokens = ["education", "project", "skills", "certification", "achievement", "awards", "interests"]
        if start_idx is not None:
            for line in lines[start_idx:]:
                if is_heading_line(line) or any(tok in line.lower() for tok in stop_tokens): break
                exp_section.append(line)
        info["experience"] = " | ".join(exp_section) if exp_section else "NOT APPLICABLE"
    else:
        info["experience"] = "NOT APPLICABLE"

    # ======= PROJECTS =======
    proj_keywords = ["Project", "Developed", "Implemented", "Built", "Designed", "Created"]
    project_lines = [line.strip() for line in text.split("\n") if any(k.lower() in line.lower() for k in proj_keywords)]
    info["projects"] = " | ".join(project_lines) if project_lines else "Not Applicable"

    # ======= SKILLS =======
    skills_keywords = ["Java", "Python", "C++", "C", "HTML", "CSS", "JavaScript", "PHP", "MySQL", "Machine Learning", "AI", "React", "Node.js", "MongoDB", "Git", "Docker", "AWS", "Azure", "GCP"]
    found_skills = [skill for skill in skills_keywords if re.search(r'\b' + re.escape(skill) + r'\b', text, re.IGNORECASE)]
    info["skills"] = ", ".join(list(set(found_skills))) if found_skills else "Not Mentioned"

    # ======= CERTIFICATIONS =======
    lines = [line.strip() for line in text.split("\n") if line.strip()]
    certs = []
    start_idx = None
    cert_heading_tokens = ["certification", "certificate"]
    def is_cert_heading(line):
        s = line.strip().lower()
        if not s or len(s) > 60: return False
        for tok in cert_heading_tokens:
            if s == tok or s.startswith(tok + " ") or s.startswith(tok + ":"): return True
        return False
    for i, line in enumerate(lines):
        if is_cert_heading(line): start_idx = i + 1; break
    if start_idx is not None:
        stop_tokens = ["education", "project", "skills", "experience", "achievement", "awards", "interests"]
        for line in lines[start_idx:]:
            if is_cert_heading(line) or any(tok in line.lower() for tok in stop_tokens): break
            if line.strip():
                clean_line = re.sub(r'^[\u2022\-\*\d.\s]+', '', line)
                certs.append(clean_line)
    info["certifications"] = " | ".join(certs) if certs else "Not Mentioned"

    return info

# --- MAIN EXECUTION BLOCK ---
def main():
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No file path provided."}))
        sys.exit(1)

    pdf_path = sys.argv[1]

    try:
        nlp = spacy.load("en_core_web_sm")
    except:
        print(json.dumps({"error": "spaCy model 'en_core_web_sm' not found. Run 'python -m spacy download en_core_web_sm'"}))
        sys.exit(1)

    pdf_text, error = extract_text_from_pdf(pdf_path)
    if error:
        print(json.dumps({"error": error}))
        sys.exit(1)
        
    if pdf_text:
        extracted_data = extract_info(pdf_text, nlp)
        print(json.dumps(extracted_data)) # Print data as JSON
    else:
        print(json.dumps({"error": "Could not extract text from PDF."}))

if __name__ == "__main__":
    main()