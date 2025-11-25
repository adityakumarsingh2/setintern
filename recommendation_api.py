import pandas as pd
import numpy as np
from sklearn.preprocessing import MinMaxScaler
from flask import Flask, request, jsonify
from flask_cors import CORS
import mysql.connector
from mysql.connector import Error
import logging

# Setup logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# ---------------------------
# 1. DATABASE CONFIGURATION
# ---------------------------
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',  # Your XAMPP MySQL username
    'password': '',  # Your XAMPP MySQL password (usually empty)
    'database': 'smartmatch_db',  # YOUR actual database name
    'port': 3306
}


# ---------------------------
# 2. SETUP SCALERS
# ---------------------------
scalers = {
    'cgpa': MinMaxScaler(feature_range=(0, 1)).fit(np.array([[0], [10]])),
    'experience_years': MinMaxScaler(feature_range=(0, 1)).fit(np.array([[0], [5]])),
    'certifications': MinMaxScaler(feature_range=(0, 1)).fit(np.array([[0], [10]]))
}

# ---------------------------
# 3. DATABASE CONNECTION FUNCTION
# ---------------------------
def get_db_connection():
    """Create and return a database connection"""
    try:
        connection = mysql.connector.connect(**DB_CONFIG)
        if connection.is_connected():
            logger.info("Successfully connected to MySQL database")
            return connection
    except Error as e:
        logger.error(f"Error connecting to MySQL database: {e}")
        return None

# ---------------------------
# 4. LOAD INTERNSHIPS FROM DATABASE
# ---------------------------
def load_internships():
    """Load all active internships from database"""
    connection = get_db_connection()
    if not connection:
        return pd.DataFrame()
    
    try:
        query = """
        SELECT 
            internship_id, 
            title, 
            company_name,
            description,
            required_domain, 
            min_cgpa, 
            required_experience, 
            min_certifications,
            importance_domain, 
            importance_cgpa, 
            importance_experience, 
            importance_certifications,
            location,
            duration_months,
            stipend,
            application_deadline
        FROM internships
        WHERE is_active = 1
        ORDER BY created_at DESC
        """
        
        df = pd.read_sql(query, connection)
        logger.info(f"Successfully loaded {len(df)} internships from database")
        return df
        
    except Error as e:
        logger.error(f"Error loading internships: {e}")
        return pd.DataFrame()
    
    finally:
        if connection.is_connected():
            connection.close()

# ---------------------------
# 5. RECOMMENDATION ALGORITHM
# ---------------------------
def recommend_internships(student_data, internships_df, scalers):
    """
    Generate personalized internship recommendations
    
    Args:
        student_data (dict): Student profile with domain, cgpa, experience_years, certifications
        internships_df (DataFrame): All available internships
        scalers (dict): Feature scalers
    
    Returns:
        DataFrame: Ranked internship recommendations with scores
    """
    if internships_df.empty:
        logger.warning("No internships available for recommendation")
        return pd.DataFrame(columns=['internship_id', 'title', 'company_name', 'total_score'])
    
    # Convert student data to DataFrame
    student = pd.DataFrame([student_data])
    student_profile = student.iloc[0]
    
    # A. Apply Hard Filters (minimum requirements)
    eligible_internships = internships_df[
        (internships_df['min_cgpa'] <= student_profile['cgpa']) &
        (internships_df['required_experience'] <= student_profile['experience_years']) &
        (internships_df['min_certifications'] <= student_profile['certifications'])
    ].copy()
    
    if eligible_internships.empty:
        logger.info("No internships match the hard filter criteria")
        return pd.DataFrame(columns=['internship_id', 'title', 'company_name', 'total_score'])
    
    # B. Scale Student Features
    s_cgpa_scaled = scalers['cgpa'].transform([[student_profile['cgpa']]])[0][0]
    s_exp_scaled = scalers['experience_years'].transform([[student_profile['experience_years']]])[0][0]
    s_certs_scaled = scalers['certifications'].transform([[student_profile['certifications']]])[0][0]
    
    # C. Calculate Component Scores
    # Domain match (binary: 1 if exact match, 0.5 if partial, 0 if no match)
    eligible_internships['score_domain'] = eligible_internships.apply(
        lambda row: (
            1.0 if row['required_domain'].lower() == student_profile['domain'].lower()
            else 0.5 if student_profile['domain'].lower() in row['required_domain'].lower() or row['required_domain'].lower() in student_profile['domain'].lower()
            else 0.0
        ) * row['importance_domain'],
        axis=1
    )
    
    # CGPA score (normalized)
    eligible_internships['score_cgpa'] = s_cgpa_scaled * eligible_internships['importance_cgpa']
    
    # Experience score (normalized)
    eligible_internships['score_exp'] = s_exp_scaled * eligible_internships['importance_experience']
    
    # Certifications score (normalized)
    eligible_internships['score_certs'] = s_certs_scaled * eligible_internships['importance_certifications']
    
    # D. Calculate Total Score
    score_cols = ['score_domain', 'score_cgpa', 'score_exp', 'score_certs']
    eligible_internships['total_score'] = eligible_internships[score_cols].sum(axis=1)
    
    # E. Rank and Return Top Recommendations
    ranked = eligible_internships.sort_values(by='total_score', ascending=False)
    
    result_columns = [
        'internship_id', 'title', 'company_name', 'description',
        'required_domain', 'location', 'duration_months', 'stipend',
        'application_deadline', 'total_score'
    ]
    
    return ranked[result_columns].head(10)  # Return top 10 recommendations

# ---------------------------
# 6. FLASK API SETUP
# ---------------------------
app = Flask(__name__)
CORS(app)  # Enable CORS for PHP frontend

# Load internships when server starts
all_internships = load_internships()

@app.route('/health', methods=['GET'])
def health_check():
    """API health check endpoint"""
    return jsonify({
        "status": "healthy",
        "internships_loaded": len(all_internships),
        "database": DB_CONFIG['database']
    })

@app.route('/recommend', methods=['POST'])
def get_recommendations():
    """
    Endpoint to get personalized internship recommendations
    
    Expected JSON payload:
    {
        "domain": "Software Development",
        "cgpa": 8.5,
        "experience_years": 1.0,
        "certifications": 2
    }
    """
    try:
        # Get student data from request
        student_data = request.get_json()
        
        if not student_data:
            return jsonify({"error": "No data received"}), 400
        
        # Validate required fields
        required_fields = ['domain', 'cgpa', 'experience_years', 'certifications']
        missing_fields = [field for field in required_fields if field not in student_data]
        
        if missing_fields:
            return jsonify({
                "error": f"Missing required fields: {', '.join(missing_fields)}"
            }), 400
        
        # Reload internships to get latest data
        current_internships = load_internships()
        
        if current_internships.empty:
            return jsonify({
                "error": "No internships available",
                "recommendations": []
            }), 200
        
        # Generate recommendations
        recommendations_df = recommend_internships(student_data, current_internships, scalers)
        
        # Convert DataFrame to JSON
        if recommendations_df.empty:
            return jsonify({
                "message": "No matching internships found for your profile",
                "recommendations": []
            }), 200
        
        # Format the response
        recommendations_json = recommendations_df.to_dict('records')
        
        # Format scores and other fields for display
        for rec in recommendations_json:
            rec['total_score'] = round(float(rec['total_score']), 2)
            rec['stipend'] = float(rec['stipend']) if rec['stipend'] else 0
            rec['application_deadline'] = str(rec['application_deadline']) if rec['application_deadline'] else None
        
        logger.info(f"Returned {len(recommendations_json)} recommendations")
        
        return jsonify({
            "success": True,
            "count": len(recommendations_json),
            "recommendations": recommendations_json
        })
    
    except Exception as e:
        logger.error(f"Error processing recommendation request: {e}")
        return jsonify({
            "error": str(e),
            "recommendations": []
        }), 500

@app.route('/internships', methods=['GET'])
def get_all_internships():
    """Get all active internships"""
    try:
        current_internships = load_internships()
        
        if current_internships.empty:
            return jsonify({
                "success": True,
                "count": 0,
                "internships": []
            })
        
        internships_json = current_internships.to_dict('records')
        
        # Format data
        for internship in internships_json:
            internship['stipend'] = float(internship['stipend']) if internship['stipend'] else 0
            internship['application_deadline'] = str(internship['application_deadline']) if internship['application_deadline'] else None
        
        return jsonify({
            "success": True,
            "count": len(internships_json),
            "internships": internships_json
        })
    
    except Exception as e:
        logger.error(f"Error fetching internships: {e}")
        return jsonify({"error": str(e)}), 500

# ---------------------------
# 7. RUN THE SERVER
# ---------------------------
if __name__ == '__main__':
    print("\n" + "="*60)
    print("🚀 INTERNSHIP RECOMMENDATION API SERVER")
    print("="*60)
    print(f"📊 Database: {DB_CONFIG['database']}")
    print(f"📍 Server: http://localhost:5000")
    print(f"🔗 Endpoints:")
    print(f"   - GET  /health (health check)")
    print(f"   - POST /recommend (get recommendations)")
    print(f"   - GET  /internships (get all internships)")
    print(f"📦 Internships loaded: {len(all_internships)}")
    print("="*60 + "\n")
    
    app.run(host='0.0.0.0', port=5000, debug=True)
