import streamlit as st
import fitz
import re
import spacy

# Load spaCy model
try:
    nlp = spacy.load("en_core_web_sm")
except:
    st.error("⚠ Please install spaCy model: python -m spacy download en_core_web_sm")
    st.stop()

# ========== STEP 1: Extract text from PDF ==========
def extract_text_from_pdf(pdf_file):
    text = ""
    with fitz.open(stream=pdf_file.read(), filetype="pdf") as doc:
        for page in doc:
            text += page.get_text()
    return text


# ========== STEP 2: Extract information ==========
def extract_info(text):
    info = {}
    doc = nlp(text)

    # ========== NAME ==========
    def extract_full_name(text):
        lines = [line.strip() for line in text.split("\n") if line.strip()]
        for line in lines[:5]:
            if "@" in line or re.search(r'\d', line):
                continue
            words = line.split()
            if 1 < len(words) <= 4 and all(w[0].isupper() for w in words if w.isalpha()):
                return line
        return None

    info["name"] = extract_full_name(text)

    # ========== EMAIL ==========
    email = re.findall(r'\S+@\S+', text)
    info["email"] = email[0] if email else None

    # ========== PHONE ==========
    phone = re.findall(r'\+?\d[\d\s-]{8,}\d', text)
    info["phone"] = phone[0] if phone else None

    # ========== EDUCATION ==========
    edu_keywords = ["B.Tech", "B.E", "M.Tech", "Bachelor", "Master", "Degree", "University", "College"]
    education_lines = [line.strip() for line in text.split("\n") if any(k in line for k in edu_keywords)]
    filtered_education = [l for l in education_lines if not any(substr in l.lower() for substr in ["coursera", "computer communications", "university of colorado"])]
    info["education"] = " | ".join(filtered_education) if filtered_education else None

    # ========== EXPERIENCE ==========
    # prepare lines for section-aware extraction
    lines = [line.strip() for line in text.split("\n") if line.strip()]
    lower_lines = [l.lower() for l in lines]

    # detect whether an explicit Experience/Work Experience heading exists
    # Only treat a line as a heading if it's a short, standalone line (to avoid matching inline words)
    exp_heading_tokens = ["experience", "work experience", "professional experience", "employment"]
    def is_heading_line(line):
        s = line.strip()
        if not s or len(s) > 60:
            return False
        for tok in exp_heading_tokens:
            if s == tok or s.startswith(tok + " ") or s.startswith(tok + ":"):
                return True
        return False

    has_exp_heading = any(is_heading_line(l) for l in lower_lines)

    if has_exp_heading:
        # collect lines under the Experience heading until the next section
        start_idx = None
        for i, l in enumerate(lower_lines):
            if any(tok in l for tok in exp_heading_tokens):
                start_idx = i + 1
                break
        exp_section = []
        stop_tokens = ["education", "project", "projects", "skills", "certification", "certifications", "achievement", "awards", "interests"]
        if start_idx is not None:
            for line in lines[start_idx:]:
                low = line.lower()
                if any(tok in low for tok in stop_tokens):
                    break
                exp_section.append(re.sub(r'^[\u2022\-\*\s]+', '', line))
        info["experience"] = " | ".join(exp_section) if exp_section else "NOT APPLICABLE"
    else:
        # No explicit Experience section — report Not Applicable
        info["experience"] = "NOT APPLICABLE"

    # ========== PROJECTS ==========
    proj_keywords = ["Project", "Developed", "Implemented", "Built", "Designed", "Created"]
    project_lines = [line.strip() for line in text.split("\n") if any(k.lower() in line.lower() for k in proj_keywords)]
    info["projects"] = " | ".join(project_lines) if project_lines else "Not Applicable"

    # ========== SKILLS ==========
    skills_keywords = ["Java", "Python", "C++", "C", "HTML", "CSS", "JavaScript", "PHP", "MySQL", "jQuery",
                       "Machine Learning", "AI", "Data Science", "Cloud", "DevOps", "Git", "GitHub"]
    found_skills = [skill for skill in skills_keywords if skill.lower() in text.lower()]
    info["skills"] = found_skills if found_skills else ["Not Mentioned"]

    # ========== CERTIFICATIONS ==========
    lines = [line.strip() for line in text.split("\n") if line.strip()]
    lower_lines = [line.lower() for line in lines]

    certs = []
    start_idx = None
    for i, line in enumerate(lower_lines):
        if "certification" in line or "certificate" in line:
            start_idx = i + 1
            break

    if start_idx is not None:
        for line in lines[start_idx:]:
            if any(word in line.lower() for word in ["achievement", "education", "experience", "project", "skills"]):
                break
            if line and not line.lower().startswith("certification"):
                certs.append(line)

    date_pattern = re.compile(r'^(?:[A-Za-z]{3,9}\s+\d{4}|\d{4})$')
    clean_certs = []
    for c in certs:
        s = c.strip()
        if date_pattern.match(s):
            continue
        s = re.sub(r'^[\u2022\-\*\s]+', '', s)
        if s:
            clean_certs.append(s)

    info["certifications"] = clean_certs if clean_certs else ["Not Mentioned"]

    return info


# ========== STEP 3: Streamlit UI ==========
st.set_page_config(page_title="Resume Extractor", layout="centered")
st.title("📄 Smart Resume Information Extractor")

uploaded_file = st.file_uploader("Upload your Resume (PDF)", type=["pdf"])

if uploaded_file:
    with st.spinner("Extracting information... ⏳"):
        text = extract_text_from_pdf(uploaded_file)
        info = extract_info(text)

    st.success("Extraction complete!")

    st.header("Extracted Details")

    # Name
    st.subheader("Name")
    st.write(info["name"] or "Not Found")

    # Email
    st.subheader("Email")
    st.write(info["email"] or "Not Found")

    # Phone
    st.subheader("Phone")
    st.write(info["phone"] or "Not Found")

    # Education
    st.subheader("Education")
    if info["education"]:
        for edu in info["education"].split(" | "):
            st.write("•", edu)
    else:
        st.write("Not Found")

    # Experience
    st.subheader("Experience")
    if info["experience"] == "NOT APPLICABLE":
        st.write("NOT APPLICABLE")
    else:
        for exp in info["experience"].split(" | "):
            st.write("•", exp)

    # Projects
    st.subheader("Projects")
    if info["projects"] == "Not Applicable":
        st.write("Not Applicable")
    else:
        for proj in info["projects"].split(" | "):
            st.write("•", proj)

    # Skills
    st.subheader("Skills")
    st.write(", ".join(info["skills"]))

    # Certifications
    st.subheader("Certifications")
    for cert in info["certifications"]:
        st.write("•", cert)

else:
    st.info("Please upload a PDF resume to begin.")