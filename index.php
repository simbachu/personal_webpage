<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jennifer Gott</title>
    <link rel="stylesheet" href="fonts/inter.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <h1>Jennifer Gott</h1>
            <ul>
                <li><a href="#about">About</a></li>
                <li><a href="#projects">Projects</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section id="about">
            <h2>About Me</h2>
            <p>Software designer. Information engineer. Illustrator.</p>
            <p>Based in Gothenburg, Sweden.</p>
            <p>Currently attending <a href="https://chasacademy.se/">Chas Academy</a> studying System Development.</p>
            <h3>Skills</h3>
            <ul>
                <li><strong>C/C++</strong>, embedded and native development</li>
                <li>Test design and program correctness</li>
                <li>Team leader, agile product owner</li>
                <li>Technical illustration</li>
                <li>Layout and typesetting</li>
            </ul>
        </section>

        <section id="projects">
            <h2>Projects</h2>
            <div class="project-grid">
                <div class="project-card">
                    <h3>Aftermarket Wiring Diagram Handbook</h3>
                    <p class="years">2017-present</p>
                    <p class="tags">Illustration, CAD, InDesign</p>
                    <p>Fully illustrated automotive wiring diagram handbooks for service technicians. Over 20 vehicle variants across internal combustion, hybrid-electric, and electric vehicles.</p>
                    <p>Process design from initial proof of concept to final production, with training of staff on different sites. Roadmap ownership and management.</p>
                </div>
                <div class="project-card">
                    <h3>Sunrise alarm clock</h3>
                    <p class="years">2025</p>
                    <p class="tags">Chas Academy, Arduino, C++, electronics</p>
                    <p>Breadboard alarm clock with sunrise simulation using daylight LED. Set alarm time and sunrise duration.</p>
                </div>
                <div class="project-card">
                    <h3>Conference room occupancy tracker</h3>
                    <p class="years">2025</p>
                    <p class="tags">Chas Academy, Arduino, C++, REST API, electronics</p>
                    <p>Room booking system with real-time occupancy information (clearing bookings on vacant rooms). Infrared and temperature sensors for occupancy detection.</p>
                    <p>Part of the Chas Challenge contest. Nominated for Best Embedded Project.</p>
                    <p><a href="https://github.com/Kusten-ar-klar-Chas-Challenge-2025/">GitHub</a></p>
                </div>
                <div class="project-card">
                    <h3>Package tracking system</h3>
                    <p class="years">2025</p>
                    <p class="tags">Chas Academy, Arduino, C++, BLE, REST API, electronics</p>
                    <p>Package tracking system with sensor packages communicating with vehicle based broker, relaying package information to backend Azure service for display in-app or on website. Temperature sensor and alarm for package temperature tracking.</p>
                    <p>Wrote hardware abstraction library to facilitate development on both espidf and Arduino platforms.</p>
                    <p><a href="https://github.com/G1-H25">GitHub</a></p>
                </div>
            </div>
        </section>

        <section id="contact">
            <h2>Contact</h2>
            <ul>
                <li>Github: <a href="https://github.com/simbachu">simbachu</a></li>
                <li>Email: <a href="mailto:simbachu@gmail.com">simbachu@gmail.com</a></li>
                <li>LinkedIn: <a href="https://www.linkedin.com/in/jennifer-jonathan-gott-2233aa294/">Jennifer Gott</a></li>
            </ul>

        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Jennifer Gott. All rights reserved.</p>
    </footer>
</body>
</html>


