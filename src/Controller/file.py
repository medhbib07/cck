import random
from datetime import datetime, timedelta

first_names = ["Jean", "Claire", "Luc", "Sophie", "Pierre", "Marie", "Paul", "Emma", "Louis", "Alice"]
last_names = ["Dupont", "Martin", "Durand", "Leroy", "Moreau", "Simon", "Laurent", "Michel", "Garcia", "David"]
sections = ["Informatique", "Mathématiques", "Physique", "Chimie", "Biologie"]
cities = ["Paris", "Lyon", "Marseille", "Toulouse", "Nice"]

def random_date(start_year=1995, end_year=2005):
    start = datetime(year=start_year, month=1, day=1)
    end = datetime(year=end_year, month=12, day=31)
    delta = end - start
    random_days = random.randint(0, delta.days)
    return (start + timedelta(days=random_days)).strftime('%Y-%m-%d')

def random_score():
    return str(round(random.uniform(8, 20), 1)) if random.random() > 0.2 else ""

students_xml = '<?xml version="1.0" encoding="UTF-8"?>\n<students>\n'

base_num_cin = 100000000000
current_year = datetime.now().year

for i in range(1, 401):
    fn = random.choice(first_names)
    ln = random.choice(last_names)
    email = f"{fn.lower()}.{ln.lower()}{i}@example.com"
    dob = random_date()
    num_cin = str(base_num_cin + i)
    section = random.choice(sections)
    score = random_score()
    niveau = random.choice(["Licence 1", "Licence 2", "Licence 3", "Master 1", "Master 2"])
    city = random.choice(cities)

    students_xml += f"    <student>\n"
    students_xml += f"        <nom>{ln}</nom>\n"
    students_xml += f"        <prenom>{fn}</prenom>\n"
    students_xml += f"        <email>{email}</email>\n"
    students_xml += f"        <dateNaissance>{dob}</dateNaissance>\n"
    students_xml += f"        <numCin>{num_cin}</numCin>\n"
    students_xml += f"        <section>{section}</section>\n"
    students_xml += f"        <score>{score}</score>\n"
    students_xml += f"        <niveau>{niveau}</niveau>\n"
    students_xml += f"        <localisation>{city}</localisation>\n"
    students_xml += f"    </student>\n"

students_xml += '</students>'

with open("students_400.xml", "w", encoding="utf-8") as f:
    f.write(students_xml)

print("students_400.xml generated successfully.")
