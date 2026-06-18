function submitForm() {
    const data = new FormData();
    data.append("name",    document.getElementById("fname").value);
    data.append("email",   document.getElementById("femail").value);
    data.append("phone",   document.getElementById("fphone").value);
    data.append("type",    document.getElementById("ftype").value);
    data.append("message", document.getElementById("fmsg").value);

    fetch("send.php", { method: "POST", body: data })
        .then(r => r.text())
        .then(res => {
            if (res === "success") {
                document.getElementById("toast").style.display = "block";
                setTimeout(() => document.getElementById("toast").style.display = "none", 4000);
            } else {
                alert("Something went wrong. Please email us directly.");
            }
        });
}