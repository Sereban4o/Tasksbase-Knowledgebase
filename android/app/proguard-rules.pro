# Keep Gson model classes (reflection-based (de)serialization)
-keep class com.tasksbase.chat.data.remote.** { *; }
-keepattributes Signature
-keepattributes *Annotation*
-dontwarn okhttp3.**
-dontwarn retrofit2.**
