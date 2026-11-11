import org.jetbrains.compose.desktop.application.dsl.TargetFormat

plugins {
    kotlin("jvm")
    id("org.jetbrains.compose")
}

kotlin {
    jvmToolchain(17)
}

dependencies {
    implementation(compose.desktop.currentOs)
    implementation(compose.material3)

    implementation("com.squareup.retrofit2:retrofit:2.11.0")
    implementation("com.squareup.retrofit2:converter-gson:2.11.0")
    implementation("com.squareup.okhttp3:okhttp:4.12.0")
    implementation("com.squareup.okhttp3:logging-interceptor:4.12.0")
    implementation("org.json:json:20240303")
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-swing:1.8.1")
}

compose.desktop {
    application {
        mainClass = "com.tasksbase.chat.desktop.MainKt"

        nativeDistributions {
            modules("jdk.unsupported")
            targetFormats(TargetFormat.Msi, TargetFormat.Exe)
            packageName = "TasksbaseChat"
            packageVersion = "1.0.2"

            windows {
                // Create a shortcut on the desktop and in the Start menu on install.
                shortcut = true
                menu = true
                menuGroup = "Tasksbase"
                // Per-user install (no admin required) and stable id for MSI upgrades.
                perUserInstall = true
                upgradeUuid = "3f2a6c1e-9b4d-4c7a-8e21-5d6f0a1b2c3d"
            }
        }
    }
}
