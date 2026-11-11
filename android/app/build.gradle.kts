plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
}

android {
    namespace = "com.tasksbase.chat"
    compileSdk = 34

    defaultConfig {
        applicationId = "com.tasksbase.chat"
        minSdk = 24
        targetSdk = 34
        versionCode = 3
        versionName = "1.0.2"

        // ── Default server address ──────────────────────────────────────────
        // Used until the user sets a server address/port inside the app.
        // 10.0.2.2 is the host machine as seen from the Android emulator.
        buildConfigField("String", "API_BASE_URL", "\"http://10.0.2.2:8000/api/v1/\"")
        buildConfigField("String", "WS_KEY", "\"chat-key\"")
    }

    // Two builds: "selfupdate" (in-app APK install) and "manual" (no install permission).
    flavorDimensions += "update"

    productFlavors {
        create("selfupdate") {
            dimension = "update"
            buildConfigField("boolean", "SELF_UPDATE", "true")
        }
        create("manual") {
            dimension = "update"
            buildConfigField("boolean", "SELF_UPDATE", "false")
        }
    }

    val hasKeystore = file("release.keystore").exists()

    signingConfigs {
        if (hasKeystore) {
            create("release") {
                storeFile = file("release.keystore")
                storePassword = System.getenv("ANDROID_KEYSTORE_PASSWORD") ?: "tasksbase"
                keyAlias = System.getenv("ANDROID_KEY_ALIAS") ?: "tasksbase"
                keyPassword = System.getenv("ANDROID_KEY_PASSWORD") ?: "tasksbase"
            }
        }
    }

    buildTypes {
        release {
            isMinifyEnabled = false
            proguardFiles(getDefaultProguardFile("proguard-android-optimize.txt"), "proguard-rules.pro")
            if (hasKeystore) {
                signingConfig = signingConfigs.getByName("release")
            }
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = "17"
    }

    buildFeatures {
        compose = true
        buildConfig = true
    }

    composeOptions {
        kotlinCompilerExtensionVersion = "1.5.14"
    }

    packaging {
        resources {
            excludes += "/META-INF/{AL2.0,LGPL2.1}"
        }
    }
}

dependencies {
    implementation("androidx.core:core-ktx:1.13.1")
    implementation("androidx.lifecycle:lifecycle-runtime-ktx:2.8.3")
    implementation("androidx.activity:activity-compose:1.9.0")

    implementation(platform("androidx.compose:compose-bom:2024.06.00"))
    implementation("androidx.compose.ui:ui")
    implementation("androidx.compose.ui:ui-graphics")
    implementation("androidx.compose.ui:ui-tooling-preview")
    implementation("androidx.compose.material3:material3")
    implementation("androidx.compose.material:material-icons-extended")
    debugImplementation("androidx.compose.ui:ui-tooling")

    implementation("io.coil-kt:coil-compose:2.7.0")
    implementation("io.coil-kt:coil-gif:2.7.0")

    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.8.1")

    implementation("com.squareup.retrofit2:retrofit:2.11.0")
    implementation("com.squareup.retrofit2:converter-gson:2.11.0")
    implementation("com.squareup.okhttp3:okhttp:4.12.0")
    implementation("com.squareup.okhttp3:logging-interceptor:4.12.0")

    implementation("androidx.datastore:datastore-preferences:1.1.1")
}
